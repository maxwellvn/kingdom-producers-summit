<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Sends one announcement to a group of registrants, by email and KingsChat.
 */
final class Announcer
{
    public const TEMPLATES = [
        'live' => [
            'label'   => 'We are live now',
            'subject' => 'We are live — join the summit now',
            'body'    => "The Kingdom Producers Summit is live now.\n\nWatch here: {watch_url}\n\nYou will need your reference {reference} and the email or KingsChat username you registered with.",
        ],
        'starting_soon' => [
            'label'   => 'Starting in an hour',
            'subject' => 'The summit starts in an hour',
            'body'    => "The Kingdom Producers Summit starts in an hour.\n\nWatch here when it begins: {watch_url}\n\nYour reference is {reference}.",
        ],
        'today' => [
            'label'   => 'It is today',
            'subject' => 'The summit is today',
            'body'    => "Today is the day, {first_name}.\n\n{summit_date}\n\nWatch online here: {watch_url}\nYour reference is {reference}.",
        ],
    ];

    /** @return array<string,string> the recipients, keyed by participation */
    public static function audiences(): array
    {
        return [
            'all'        => 'Everyone confirmed',
            'online'     => 'Online only',
            'onsite'     => 'Onsite only',
            'initiative' => 'Initiative only',
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function recipients(string $audience): array
    {
        $sql = "SELECT reference, first_name, last_name, email, kingschat_username, participation
                FROM registrations
                WHERE status = 'confirmed'";
        $params = [];

        if ($audience !== 'all') {
            $sql .= ' AND participation = :participation';
            $params['participation'] = $audience;
        }

        $stmt = Database::connection()->prepare($sql . ' ORDER BY created_at');
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array{sent:int, emailed:int, messaged:int, failed:int}
     */
    public static function send(string $audience, string $subject, string $body, bool $byEmail, bool $byKingsChat): array
    {
        $recipients = self::recipients($audience);
        $result = ['sent' => 0, 'emailed' => 0, 'messaged' => 0, 'failed' => 0];

        foreach ($recipients as $person) {
            $text = self::fill($body, $person);
            $delivered = false;

            if ($byEmail) {
                try {
                    (new Mailer())->send(
                        (string) $person['email'],
                        self::fill($subject, $person),
                        self::html($text, $person),
                        $text,
                        ['Reply-To' => contact_email()]
                    );
                    $result['emailed']++;
                    $delivered = true;
                } catch (\Throwable $e) {
                    error_log('Announcement email failed for ' . $person['reference'] . ': ' . $e->getMessage());
                }
            }

            if ($byKingsChat && trim((string) ($person['kingschat_username'] ?? '')) !== '') {
                [$ok] = (new KingsChatClient())->send((string) $person['kingschat_username'], $text);
                if ($ok) {
                    $result['messaged']++;
                    $delivered = true;
                }
            }

            $delivered ? $result['sent']++ : $result['failed']++;
        }

        return $result;
    }

    /** Swap the placeholders for this person's own details. */
    public static function fill(string $text, array $person): string
    {
        $summit = (array) config('app.summit');

        return strtr($text, [
            '{first_name}'  => (string) $person['first_name'],
            '{last_name}'   => (string) $person['last_name'],
            '{reference}'   => (string) $person['reference'],
            '{watch_url}'   => site_url() . '/watch',
            '{summit_date}' => (string) ($summit['date_text'] ?? ''),
            '{summit_city}' => (string) ($summit['city'] ?? ''),
        ]);
    }

    private static function html(string $text, array $person): string
    {
        $paragraphs = array_filter(array_map('trim', preg_split('/\n{2,}/', $text) ?: []));
        $body = '';
        foreach ($paragraphs as $paragraph) {
            $body .= '<p style="margin:0 0 16px;color:#1b2242;font-size:16px;line-height:1.6">'
                . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
        }

        return '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="color-scheme" content="light only"></head>'
            . '<body style="margin:0;background:#eae3d2;font-family:Arial,Helvetica,sans-serif">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eae3d2">'
            . '<tr><td align="center" style="padding:32px 16px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#f3eee2;border:1px solid #c9c1af">'
            . '<tr><td style="padding:32px">' . $body . '</td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">'
            . 'The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · '
            . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8')
            . '</td></tr></table></td></tr></table></body></html>';
    }
}

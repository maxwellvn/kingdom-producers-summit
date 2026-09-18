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
        'week_before' => [
            'label'   => 'One week to go',
            'subject' => 'One week to the summit, {first_name}',
            'body'    => "Hello {first_name},\n\nThe Kingdom Producers Summit is one week away: {summit_date}, {summit_venue}.\n\nYour reference is {reference}. Keep it handy; it is your pass on the day.\n\nSee you there.",
        ],
        'day_before' => [
            'label'   => 'It is tomorrow',
            'subject' => 'The summit is tomorrow',
            'body'    => "{first_name}, the summit is tomorrow.\n\n{summit_date}\n{summit_venue}\n\nOnsite: bring your reference {reference} and arrive by 11:30 for registration.\nOnline: watch at {watch_url} and sign in with your email or reference.",
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
            [$emailed, $messaged] = self::deliver($person, $subject, $body, $byEmail, $byKingsChat);
            $result['emailed'] += (int) $emailed;
            $result['messaged'] += (int) $messaged;
            ($emailed || $messaged) ? $result['sent']++ : $result['failed']++;
        }

        return $result;
    }

    /**
     * One person, filled in and sent by whichever channels are asked for.
     * @return array{0:bool,1:bool} emailed, messaged
     */
    public static function deliver(array $person, string $subject, string $body, bool $byEmail, bool $byKingsChat): array
    {
        $text = self::fill($body, $person);
        $emailed = $messaged = false;

        if ($byEmail) {
            try {
                (new Mailer())->send(
                    (string) $person['email'],
                    self::fill($subject, $person),
                    self::html($text, $person),
                    $text,
                    ['Reply-To' => contact_email()]
                );
                $emailed = true;
            } catch (\Throwable $e) {
                error_log('Announcement email failed for ' . $person['reference'] . ': ' . $e->getMessage());
            }
        }

        if ($byKingsChat && trim((string) ($person['kingschat_username'] ?? '')) !== '') {
            try {
                [$messaged] = (new KingsChatClient())->send((string) $person['kingschat_username'], $text);
            } catch (\Throwable $e) {
                error_log('Announcement KingsChat failed for ' . $person['reference'] . ': ' . $e->getMessage());
            }
        }

        return [$emailed, (bool) $messaged];
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
            '{summit_venue}' => implode(', ', array_filter([$summit['venue']['unit'] ?? '', $summit['venue']['name'] ?? '', $summit['venue']['street'] ?? '', $summit['venue']['postcode'] ?? ''])),
            '{email}'       => (string) ($person['email'] ?? ''),
            '{participation}' => ['onsite' => 'onsite', 'online' => 'online', 'initiative' => 'the initiative'][$person['participation'] ?? ''] ?? '',
            '{days_to_go}'  => (string) max(0, (int) ceil((strtotime((string) ($summit['starts_at'] ?? 'now')) - time()) / 86400)),
            '{register_url}' => site_url() . '/register',
            '{sponsor_url}' => site_url() . '/sponsor',
        ]);
    }

    /** The placeholders an organiser may type, for the hint under the box. */
    public const PLACEHOLDERS = ['first_name', 'last_name', 'email', 'reference', 'participation', 'days_to_go', 'summit_date', 'summit_venue', 'summit_city', 'watch_url', 'register_url', 'sponsor_url'];

    /** @param array{sent:int, emailed:int, messaged:int, failed:int} $r */
    public static function summary(array $r): string
    {
        return sprintf('Sent to %d of %d: %d emailed, %d messaged on KingsChat%s.',
            $r['sent'], $r['sent'] + $r['failed'], $r['emailed'], $r['messaged'],
            $r['failed'] ? ', ' . $r['failed'] . ' could not be reached' : '');
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

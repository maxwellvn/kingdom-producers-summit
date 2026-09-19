<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Registration;

/**
 * Sends one announcement to a group of registrants, by email and KingsChat.
 */
final class Announcer
{
    public const TEMPLATES = [
        'live' => [
            'label'   => 'We are live now',
            'subject' => 'We are live — join the summit now',
            'body'    => "The Kingdom Producers Summit is live now.\n\n{online_only}Watch here, no sign-in needed: {watch_url}\n\nThe link is yours alone; please do not forward it.{/online_only}{onsite_only}Doors are open at {summit_venue}. Bring your reference {reference}.\n\nDirections: {directions_url}\n\nNot able to be in the room? Watch online instead: {watch_url}{/onsite_only}",
        ],
        'starting_soon' => [
            'label'   => 'Starting in an hour',
            'subject' => 'The summit starts in an hour',
            'body'    => "The Kingdom Producers Summit starts in an hour.\n\n{online_only}Watch here when it begins: {watch_url}{/online_only}{onsite_only}Heading to {summit_venue}? Directions: {directions_url}\n\nNot able to be in the room? Watch online instead: {watch_url}\n\nHave your reference {reference} ready at the door.{/onsite_only}",
        ],
        'week_before' => [
            'label'   => 'One week to go',
            'subject' => 'One week to the summit, {first_name}',
            'body'    => "Hello {first_name},\n\nThe Kingdom Producers Summit is one week away: {summit_date}.\n\n{onsite_only}{summit_venue}. Your reference {reference} is your pass on the day. Directions: {directions_url}{/onsite_only}{online_only}Your watch link will come the day before; nothing to do until then.{/online_only}",
        ],
        'day_before' => [
            'label'   => 'It is tomorrow',
            'subject' => 'GET READY. GET SET… PRODUCE! It is tomorrow, {first_name}',
            'body'    => "GET READY. GET SET… PRODUCE!\n\n"
                . "{first_name}, the Inaugural LoveWorld Kingdom Producers Summit — London Edition 2026 launches TOMORROW, and your place is secured.\n\n"
                . "Featuring our Keynote Speaker, the Highly Esteemed Pastor Rita Ijomah — Head of Service, alongside distinguished speakers from the worlds of business, finance, production and investment.\n\n"
                . "TOMORROW — {summit_date} (GMT+1)\n"
                . "{onsite_only}WHERE: {summit_venue}\n\n"
                . "Bring your reference {reference}; it is your pass at the door. Arrive by 11:30 for registration.\n\n"
                . "Get directions: {directions_url}\n\n{/onsite_only}"
                . "{online_only}WHERE: LIVE ONLINE, wherever you are in the world\n\n"
                . "Whether you are on the icy slopes of Antarctica, at the peak of Mount Everest, in the sunshine of Los Angeles or deep in the Amazon, your seat is ready. This link is yours alone and signs you straight in from 12 noon:\n\n"
                . "Watch live: {watch_url}\n\n{/online_only}"
                . "Know someone who should be there? Registration is FREE, onsite and online. Share this link anywhere: {share_url}",
        ],
        'today' => [
            'label'   => 'It is today',
            'subject' => 'The summit is today',
            'body'    => "Today is the day, {first_name}.\n\n{summit_date}\n\n{online_only}Watch online here: {watch_url}{/online_only}{onsite_only}See you at {summit_venue}. Directions: {directions_url}\n\nIf you cannot make it to the room, watch online: {watch_url}\n\nYour reference is {reference}.{/onsite_only}",
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
                WHERE status = 'confirmed' AND email NOT LIKE '%@loadtest.invalid'";
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

        $work = static function () use ($recipients, $subject, $body, $byEmail, $byKingsChat, &$result): void {
            foreach ($recipients as $person) {
                [$emailed, $messaged] = self::deliver($person, $subject, $body, $byEmail, $byKingsChat);
                $result['emailed'] += (int) $emailed;
                $result['messaged'] += (int) $messaged;
                ($emailed || $messaged) ? $result['sent']++ : $result['failed']++;
            }
        };
        $work();

        return $result;
    }

    /**
     * One person, filled in and sent by whichever channels are asked for.
     * @return array{0:bool,1:bool} emailed, messaged
     */
    /** Email one person; the mail server's reply surfaces as the exception. */
    public static function deliverOrThrow(array $person, string $subject, string $body): void
    {
        $text = self::fill($body, $person);
        (new Mailer())->send((string) $person['email'], self::fill($subject, $person), self::html($text, $person), $text, ['Reply-To' => contact_email()]);
    }

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

    /**
     * Where we are relative to the start, in words a person would use:
     * "It's tomorrow", "It starts in 3 hours", "We're live now", "It was on Saturday".
     * Written so it reads as the opening of a sentence.
     */
    public static function timing(?int $now = null): array
    {
        $now ??= time();
        $start = (int) strtotime((string) config('app.summit.starts_at'));
        $tz = new \DateTimeZone('Europe/London');
        $s = (new \DateTimeImmutable('@' . $start))->setTimezone($tz);
        $n = (new \DateTimeImmutable('@' . $now))->setTimezone($tz);
        $diff = $start - $now;
        $days = (int) $n->setTime(0, 0)->diff($s->setTime(0, 0))->format('%r%a');
        $timeText = $s->format('g:i') === '12:00' ? '12 noon' : $s->format('g:ia');

        if ($diff <= 0 && $diff > -8 * 3600) {
            return ['key' => 'live', 'lead' => "We're live now", 'detail' => 'The summit started at ' . $timeText . ' today.'];
        }
        if ($diff <= -8 * 3600) {
            return ['key' => 'past', 'lead' => 'The summit was on ' . $s->format('l j F'), 'detail' => 'Thank you for being part of it.'];
        }
        if ($days === 0) {
            $hours = (int) floor($diff / 3600);
            $mins = (int) floor(($diff % 3600) / 60);
            $lead = $hours >= 1 ? "It starts in {$hours} hour" . ($hours === 1 ? '' : 's') . ($mins >= 15 && $hours < 3 ? " and {$mins} minutes" : '')
                                : "It starts in {$mins} minute" . ($mins === 1 ? '' : 's');
            return ['key' => 'today', 'lead' => $lead, 'detail' => "Today at {$timeText}."];
        }
        if ($days === 1) {
            return ['key' => 'tomorrow', 'lead' => "It's tomorrow", 'detail' => $s->format('l j F') . " at {$timeText}."];
        }
        if ($days <= 7) {
            return ['key' => 'week', 'lead' => "It's this " . $s->format('l') . ", in {$days} days", 'detail' => $s->format('l j F') . " at {$timeText}."];
        }

        return ['key' => 'later', 'lead' => "It's in {$days} days", 'detail' => $s->format('l j F') . " at {$timeText}."];
    }

    /** The live-link message, sent to one person by email and KingsChat. Onsite and online only. */
    public const LIVE_LINK = [
        'subject' => '{timing_lead}: your link to watch the summit live',
        'body'    => "Hello {first_name},\n\n{timing_lead}. {timing_detail}\n\nHere is your personal link to watch the Kingdom Producers Summit live. It is yours alone and signs you straight in; please do not forward it.\n\nWatch live: {watch_url}\n\n{onsite_only}You are registered to attend in the room, so this is for following along from elsewhere if you need to. Directions: {directions_url}{/onsite_only}\n\nThe Loveworld Consulate, United Kingdom",
    ];

    /** Send someone their watch link again. */
    public static function sendLiveLink(array $person, bool $byEmail = true, bool $byKingsChat = true): array
    {

        return self::deliver($person, self::LIVE_LINK['subject'], self::LIVE_LINK['body'], $byEmail, $byKingsChat);
    }

    /** Send an onsite person their pass again: the confirmation email with the QR, and the KingsChat confirmation. */
    public static function sendPass(array $person, bool $byEmail = true, bool $byKingsChat = true): array
    {
        if ((string) $person['participation'] !== 'onsite') {
            return [false, false];
        }
        // The confirmation email needs the whole row, not the slice announcements use.
        $person = Registration::findByReference((string) $person['reference']) ?? $person;
        $emailed = $messaged = false;
        if ($byEmail) {
            try {
                (new RegistrationMail())->sendPass($person, self::timing());
                $emailed = true;
            } catch (\Throwable $e) {
                error_log('Pass email failed for ' . $person['reference'] . ': ' . $e->getMessage());
            }
        }
        if ($byKingsChat) {
            [$messaged] = (new KingsChatNotifier())->sendPass($person, self::timing());
        }

        return [$emailed, (bool) $messaged];
    }

    /** Swap the placeholders for this person's own details. */
    public static function fill(string $text, array $person): string
    {
        $summit = (array) config('app.summit');
        $path = (string) ($person['participation'] ?? '');

        // {online_only}…{/online_only} and {onsite_only}…{/onsite_only}: each person
        // sees only the part meant for them, so one message serves both groups.
        foreach (['online', 'onsite', 'initiative'] as $only) {
            $text = (string) preg_replace_callback(
                '/\\{' . $only . '_only\\}(.*?)\\{\\/' . $only . '_only\\}/s',
                static fn (array $m) => $path === $only ? $m[1] : '',
                $text
            );
        }

        return strtr($text, [
            '{first_name}'  => (string) $person['first_name'],
            '{last_name}'   => (string) $person['last_name'],
            '{reference}'   => (string) $person['reference'],
            // Online and onsite both get a signed watch link: an onsite person may follow from elsewhere.
            '{watch_url}'   => site_url() . '/watch?pass=' . rawurlencode(AttendanceService::tokenFor((string) $person['reference'])),
            '{directions_url}' => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode((string) ($summit['venue']['query'] ?? '')),
            '{summit_date}' => (string) ($summit['date_text'] ?? ''),
            '{summit_city}' => (string) ($summit['city'] ?? ''),
            '{summit_venue}' => implode(', ', array_filter([$summit['venue']['unit'] ?? '', $summit['venue']['name'] ?? '', $summit['venue']['street'] ?? '', $summit['venue']['postcode'] ?? ''])),
            '{email}'       => (string) ($person['email'] ?? ''),
            '{participation}' => ['onsite' => 'onsite', 'online' => 'online', 'initiative' => 'the initiative'][$person['participation'] ?? ''] ?? '',
            '{days_to_go}'  => (string) max(0, (int) ceil((strtotime((string) ($summit['starts_at'] ?? 'now')) - time()) / 86400)),
            '{register_url}' => site_url() . '/register',
            '{timing_lead}'   => self::timing()['lead'],
            '{timing_detail}' => self::timing()['detail'],
            // The person's own QR pass: the confirmation page carries it, signed to them. Onsite only.
            '{qr_url}'      => $path === 'onsite' ? site_url() . '/register/confirmed?access=' . rawurlencode(AttendanceService::tokenFor((string) $person['reference'])) : '',
            '{qr_image_url}' => $path === 'onsite' ? site_url() . '/access/qr?token=' . rawurlencode(AttendanceService::tokenFor((string) $person['reference'])) : '',
            '{share_url}'   => site_url() . '/share',
            '{sponsor_url}' => site_url() . '/sponsor',
        ]);
    }

    /** The placeholders an organiser may type, for the hint under the box. */
    public const PLACEHOLDERS = ['first_name', 'last_name', 'email', 'reference', 'participation', 'days_to_go', 'summit_date', 'summit_venue', 'summit_city', 'watch_url', 'qr_url', 'qr_image_url', 'timing_lead', 'timing_detail', 'directions_url', 'share_url', 'register_url', 'sponsor_url', 'online_only}…{/online_only', 'onsite_only}…{/onsite_only'];

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
        $button = static function (string $label, string $url): string {
            return '<p style="margin:0 0 20px"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="display:inline-block;padding:14px 22px;background:#b4232b;color:#f3eee2;text-decoration:none;font:700 14px Arial,sans-serif;letter-spacing:1px;text-transform:uppercase">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' &rarr;</a></p>';
        };
        foreach ($paragraphs as $paragraph) {
            // "Watch here: https://…" becomes text plus a button; the raw address stays out of the way.
            if (preg_match('/^(.*?)\\s*(https?:\\/\\/\\S+)$/s', $paragraph, $m)) {
                $url = $m[2];
                $label = str_contains($url, 'google.com/maps') ? 'Get directions'
                    : (str_contains($url, '/watch') ? 'Watch live'
                    : (str_contains($url, '/register/confirmed?access=') ? 'Open my QR pass'
                    : (str_ends_with($url, '/share') ? 'Share the registration link' : 'Open')));
                $lead = rtrim(trim($m[1]), ':');
                $body .= ($lead !== '' ? '<p style="margin:0 0 10px;color:#1b2242;font-size:16px;line-height:1.6">' . nl2br(htmlspecialchars($lead, ENT_QUOTES, 'UTF-8')) . '</p>' : '')
                    . $button($label, $url);
                continue;
            }
            $html = htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8');
            $html = (string) preg_replace('~(https?://[^\\s<]+)~', '<a href="$1" style="color:#b4232b">$1</a>', $html);
            $body .= '<p style="margin:0 0 16px;color:#1b2242;font-size:16px;line-height:1.6">' . nl2br($html) . '</p>';
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

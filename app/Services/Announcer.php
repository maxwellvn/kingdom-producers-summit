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
        'thank_you' => [
            'label'   => 'Thank you (after the summit)',
            'subject' => 'THE LOVEWORLD CONSULATE UK SAYS THANK YOU!',
            'body'    => "*THE LOVEWORLD CONSULATE UK SAYS THANK YOU!*\n\n💥🔥 𝐖𝐇𝐀𝐓 𝐀 𝐃𝐀𝐘. 𝐖𝐇𝐀𝐓 𝐀 𝐌𝐎𝐌𝐄𝐍𝐓. 𝐖𝐇𝐀𝐓 𝐀 𝐁𝐄𝐆𝐈𝐍𝐍𝐈𝐍𝐆!\n\nTo every Kingdom Producer who filled the room at Angel Studios, and to everyone who joined us LIVE from across the world — you made the inaugural LoveWorld Kingdom Producers Summit — London 2026 truly special. 🌍🚀\n\nYou came. You listened. You learned. You connected. You made the commitment.\n\n𝗔𝗻𝗱 𝗻𝗼𝘄… 𝗪𝗘 𝗣𝗥𝗢𝗗𝗨𝗖𝗘! 💥\n\nThe lights may have gone down on today’s stage, but the movement has only just begun. The ideas, insights and commitments made today now move from the room into action.\n\n𝗧𝗼 𝗼𝘂𝗿 𝗛𝗶𝗴𝗵𝗹𝘆 𝗘𝘀𝘁𝗲𝗲𝗺𝗲𝗱 𝗦𝗽𝗲𝗮𝗸𝗲𝗿𝘀, 𝗽𝗮𝗿𝘁𝗶𝗰𝗶𝗽𝗮𝗻𝘁, 𝗽𝗮𝗿𝘁𝗻𝗲𝗿, 𝘃𝗼𝗹𝘂𝗻𝘁𝗲𝗲𝗿 𝗮𝗻𝗱 𝗲𝘃𝗲𝗿𝘆𝗼𝗻𝗲 𝘄𝗵𝗼 𝗰𝗼𝗻𝗻𝗲𝗰𝘁𝗲𝗱 𝗼𝗻𝗹𝗶𝗻𝗲 — 𝗧𝗛𝗔𝗡𝗞 𝗬𝗢𝗨 𝗳𝗼𝗿 𝗯𝗲𝗶𝗻𝗴 𝗽𝗮𝗿𝘁 𝗼𝗳 𝘁𝗵𝗲 𝗯𝗲𝗴𝗶𝗻𝗻𝗶𝗻𝗴 𝗼𝗳 𝘀𝗼𝗺𝗲𝘁𝗵𝗶𝗻𝗴 𝗲𝘅𝘁𝗿𝗮𝗼𝗿𝗱𝗶𝗻𝗮𝗿𝘆. 💙✨\n\n🔥 London was only the beginning… Manchester, Ireland and Birmingham — GET READY! 👀🚀",
        ],
        'initiative' => [
            'label'   => '90-Day Producer Challenge (after the summit)',
            'subject' => '🎉 Congratulations, Kingdom Producer. Your 90 days begin now',
            'body'    => "🎉 CONGRATULATIONS, KINGDOM PRODUCER!\n\nYou took the first step by participating in the Loveworld Kingdom Producers Summit — London Edition 2026, receiving the knowledge, insights and charge to move from a consumer to a PRODUCER. But the Summit was only the beginning. Now, it’s time to turn everything you received into tangible, measurable results! 🚀🔥\n\n𝐘𝐎𝐔 𝐇𝐄𝐀𝐑𝐃 𝐓𝐇𝐄 𝐂𝐀𝐋𝐋, 𝐍𝐎𝐖 𝐀𝐂𝐓!\n\nAt the LoveWorld Kingdom Producers Summit — London Edition 2026, Esteemed Pastor Nike Gbenga-Kehinde, Executive Minister of Cost Economy, challenged us to move beyond inspiration into deliberate action through the 90-Day Producer Challenge.\n\nWatch the session by the Executive Minister of Cost Economy: https://www.kingsch.at/p/ci96Z0R\n\nFor the next 90 days, the question is simple: “How can you use what you already have better, eliminate waste, reduce unnecessary costs and release more resources for greater Kingdom productivity?”\n\nThis is where the Kingdom Producers Initiative comes in. Through the Initiative, we’ll journey together with practical actions, accountability, knowledge, support and measurable progress as we move from CONSUMERS to PRODUCERS.\n\n🔥 Your first step into the 90-Day Producer Challenge is to make the Kingdom Producers Commitment and to join the Kingdom Producers Initiative today.\n\nMake the commitment now: {commitment_url}\n\nJoin the Kingdom Producers Initiative now: {initiative_url}\n\nCongratulations once again! Your 90 days of intentional action, productivity and measurable results begins! 🚀🔥",
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
            'checked_in' => 'Checked in at the venue',
            'watched'    => 'Watched online',
            'attended'   => 'Checked in or watched online',
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function recipients(string $audience): array
    {
        $sql = "SELECT reference, first_name, last_name, email, kingschat_username, participation
                FROM registrations
                WHERE status = 'confirmed' AND email NOT LIKE '%@loadtest.invalid'";
        $params = [];

        $checkedIn = 'id IN (SELECT registration_id FROM attendances)';
        $watched = 'reference IN (SELECT reference FROM watch_passes)';
        if ($audience === 'checked_in') {
            $sql .= " AND {$checkedIn}";
        } elseif ($audience === 'watched') {
            $sql .= " AND {$watched}";
        } elseif ($audience === 'attended') {
            $sql .= " AND ({$checkedIn} OR {$watched})";
        } elseif ($audience !== 'all') {
            $sql .= ' AND participation = :participation';
            $params['participation'] = $audience;
        }

        $stmt = Database::connection()->prepare($sql . ' ORDER BY created_at');
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    private const MAX_ATTEMPTS = 5;
    private const GAP_MICROSECONDS = 3000000; // three seconds between emails keeps the mail host calm

    /** Put one row per recipient in the queue. Safe to call again; existing rows are kept. */
    public static function enqueue(array $announcement): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO announcement_deliveries (announcement_id, reference, email) VALUES (?, ?, ?)'
        );
        foreach (self::recipients((string) $announcement['audience']) as $person) {
            $stmt->execute([(int) $announcement['id'], $person['reference'], $person['email']]);
        }
    }

    /**
     * Work through the queue for one announcement until it is empty, the time is up, or the
     * mail host throttles. Returns 'done', 'more' (call again next minute) or 'throttled'.
     */
    public static function drain(array $announcement, int $seconds = 50): string
    {
        $pdo = Database::connection();
        $id = (int) $announcement['id'];
        // A runner that died mid-send leaves rows in 'sending'; hand them back after ten minutes.
        $pdo->prepare("UPDATE announcement_deliveries SET status = 'pending' WHERE announcement_id = ? AND status = 'sending' AND claimed_at < ?")->execute([$id, date('Y-m-d H:i:s', time() - 600)]);

        $deadline = time() + $seconds;
        $byEmail = (bool) $announcement['by_email'];
        $byKingsChat = (bool) $announcement['by_kingschat'];

        while (time() < $deadline) {
            $row = $pdo->prepare("SELECT d.*, r.first_name, r.last_name, r.kingschat_username, r.participation
                                  FROM announcement_deliveries d JOIN registrations r ON r.reference = d.reference
                                  WHERE d.announcement_id = ? AND d.status = 'pending' ORDER BY d.id LIMIT 1");
            $row->execute([$id]);
            $person = $row->fetch();
            if (!$person) {
                return 'done';
            }
            // Claim it so a second runner cannot send the same email.
            $claim = $pdo->prepare("UPDATE announcement_deliveries SET status = 'sending', claimed_at = ?, attempts = attempts + 1 WHERE id = ? AND status = 'pending'");
            $claim->execute([date('Y-m-d H:i:s'), (int) $person['id']]);
            if ($claim->rowCount() === 0) {
                continue;
            }

            try {
                if ($byEmail) {
                    self::deliverOrThrow($person, (string) $announcement['subject'], (string) $announcement['body']);
                }
                if ($byKingsChat && trim((string) ($person['kingschat_username'] ?? '')) !== '') {
                    try {
                        (new KingsChatClient())->send((string) $person['kingschat_username'], self::fill((string) $announcement['body'], $person));
                    } catch (\Throwable $e) {
                        error_log('Announcement KingsChat failed for ' . $person['reference'] . ': ' . $e->getMessage());
                    }
                }
                $pdo->prepare("UPDATE announcement_deliveries SET status = 'sent', sent_at = ?, last_error = NULL WHERE id = ?")->execute([date('Y-m-d H:i:s'), (int) $person['id']]);
            } catch (\Throwable $e) {
                $error = mb_substr($e->getMessage(), 0, 255);
                if (BulkSender::throttled($e)) {
                    // Not this person's fault: back to pending, hold the whole announcement for as long as
                    // the host asked (five minutes when it did not say), then carry on from here.
                    $wait = self::waitFromReply($error);
                    $pdo->prepare("UPDATE announcement_deliveries SET status = 'pending', attempts = attempts - 1, last_error = ? WHERE id = ?")->execute([$error, (int) $person['id']]);
                    $pdo->prepare("UPDATE announcements SET resume_at = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() + $wait), $id]);
                    error_log('Announcement #' . $id . ' throttled by the mail host, resuming in ' . $wait . 's: ' . $error);
                    return 'throttled';
                }
                $final = (int) $person['attempts'] >= self::MAX_ATTEMPTS;
                $pdo->prepare("UPDATE announcement_deliveries SET status = ?, last_error = ? WHERE id = ?")->execute([$final ? 'failed' : 'pending', $error, (int) $person['id']]);
                error_log('Announcement email failed for ' . $person['reference'] . ' (attempt ' . $person['attempts'] . '): ' . $error);
            }
            usleep(self::GAP_MICROSECONDS);
        }

        return 'more';
    }

    /** Seconds to wait, read from the host's reply when it names a time; five minutes otherwise. */
    public static function waitFromReply(string $reply): int
    {
        if (preg_match('/(\d+)\s*(second|sec|minute|min|hour|hr)s?\b/i', $reply, $m)) {
            $unit = strtolower($m[2][0]);
            $seconds = (int) $m[1] * ($unit === 'h' ? 3600 : ($unit === 'm' ? 60 : 1));
            return max(30, min($seconds + 5, 6 * 3600));
        }
        if (preg_match('/retry[- ]after[:=]?\s*(\d+)/i', $reply, $m)) {
            return max(30, min((int) $m[1] + 5, 6 * 3600));
        }
        return 300;
    }

    /** @return array{sent:int,pending:int,failed:int,total:int} */
    public static function progress(int $announcementId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT SUM(status = 'sent') AS sent, SUM(status IN ('pending','sending')) AS pending, SUM(status = 'failed') AS failed, COUNT(*) AS total
             FROM announcement_deliveries WHERE announcement_id = ?"
        );
        $stmt->execute([$announcementId]);
        $r = $stmt->fetch() ?: [];

        return ['sent' => (int) ($r['sent'] ?? 0), 'pending' => (int) ($r['pending'] ?? 0), 'failed' => (int) ($r['failed'] ?? 0), 'total' => (int) ($r['total'] ?? 0)];
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

        $emailed = $messaged = false;
        if ($byEmail) {
            try {
                self::deliverOrThrow($person, self::LIVE_LINK['subject'], self::LIVE_LINK['body']);
                $emailed = true;
            } catch (\Throwable $e) {
                error_log('Live link email failed for ' . $person['reference'] . ': ' . $e->getMessage());
            }
        }
        if ($byKingsChat && trim((string) ($person['kingschat_username'] ?? '')) !== '') {
            try {
                [$messaged] = (new KingsChatClient())->send((string) $person['kingschat_username'], self::fill(self::LIVE_LINK['body'], $person));
            } catch (\Throwable $e) {
                error_log('Live link KingsChat failed for ' . $person['reference'] . ': ' . $e->getMessage());
            }
        }

        return [$emailed, (bool) $messaged];
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
            '{commitment_url}' => site_url() . '/commitment',
            '{initiative_url}' => site_url() . '/register?mode=initiative',
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
    public const PLACEHOLDERS = ['first_name', 'last_name', 'email', 'reference', 'participation', 'days_to_go', 'summit_date', 'summit_venue', 'summit_city', 'watch_url', 'qr_url', 'qr_image_url', 'timing_lead', 'timing_detail', 'directions_url', 'share_url', 'register_url', 'sponsor_url', 'commitment_url', 'initiative_url', 'online_only}…{/online_only', 'onsite_only}…{/onsite_only'];

    /** @param array{sent:int, emailed:int, messaged:int, failed:int} $r */
    public static function summary(array $p): string
    {
        return sprintf('Sent to %d of %d%s.', $p['sent'], $p['total'], $p['failed'] ? ', ' . $p['failed'] . ' could not be reached' : '');
    }

    private static function html(string $text, array $person): string
    {
        $paragraphs = array_filter(array_map('trim', preg_split('/\n{2,}/', $text) ?: []));
        $body = '';
        $button = static function (string $label, string $url, string $bg = '#b4232b'): string {
            return '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 22px"><tr><td bgcolor="' . $bg . '" style="border-radius:6px;background:' . $bg . '">'
                . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="display:inline-block;padding:15px 26px;color:#f3eee2;text-decoration:none;font:700 14px Arial,sans-serif;letter-spacing:1.2px;text-transform:uppercase">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' &nbsp;&rarr;</a></td></tr></table>';
        };
        $headline = '';
        // Lines written in bold Unicode letters or shouted in caps are headings, not paragraphs.
        $isHeading = static fn (string $t): bool => mb_strlen($t) < 90 && !str_contains($t, "\n")
            && (preg_match('/[\x{1D400}-\x{1D7FF}]/u', $t) || (mb_strtoupper($t) === $t && preg_match('/[A-Z]{3}/', $t)));
        $first = true;
        foreach ($paragraphs as $paragraph) {
            if ($first && $isHeading($paragraph)) {
                $headline = htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8');
                $first = false;
                continue;
            }
            $first = false;
            if ($isHeading($paragraph)) {
                $body .= '<h2 style="margin:28px 0 12px;color:#b4232b;font:700 20px/1.25 Arial,sans-serif;letter-spacing:.5px">' . htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8') . '</h2>';
                continue;
            }
            // "Watch here: https://…" becomes text plus a button; the raw address stays out of the way.
            if (preg_match('/^(.*?)\\s*(https?:\\/\\/\\S+)$/s', $paragraph, $m)) {
                $url = $m[2];
                $lead = rtrim(trim($m[1]), ':');
                [$label, $bg] = match (true) {
                    str_contains($url, 'google.com/maps') => ['Get directions', '#b4232b'],
                    str_contains($url, '/register/confirmed?access=') => ['Open my QR pass', '#b4232b'],
                    str_contains($url, '/commitment') => ['Make the commitment', '#b4232b'],
                    str_contains($url, 'mode=initiative') => ['Join the initiative', '#1b2242'],
                    str_contains($url, 'kingsch.at') => ['Watch the session', '#1b2242'],
                    str_contains($url, '/watch') => ['Watch live', '#b4232b'],
                    str_ends_with($url, '/share') => ['Share the registration link', '#b4232b'],
                    default => [$lead !== '' && mb_strlen($lead) <= 48 ? $lead : 'Open', '#b4232b'],
                };
                // A short lead ("Make the commitment now") is the button itself; a long one stays as text above it.
                $showLead = $lead !== '' && mb_strlen($lead) > 48;
                $body .= ($showLead ? '<p style="margin:0 0 10px;color:#1b2242;font-size:16px;line-height:1.6">' . nl2br(htmlspecialchars($lead, ENT_QUOTES, 'UTF-8')) . '</p>' : '')
                    . $button($label, $url, $bg);
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
            . ($headline !== '' ? '<tr><td bgcolor="#1b2242" style="padding:34px 32px 30px;background:#1b2242">'
                . '<p style="margin:0 0 10px;color:#aaaebe;font:11px monospace;letter-spacing:2px;text-transform:uppercase">Kingdom Producers Summit · ' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . '</p>'
                . '<h1 style="margin:0;color:#f3eee2;font:700 26px/1.2 Arial,sans-serif;letter-spacing:.3px">' . $headline . '</h1></td></tr>' : '')
            . '<tr><td style="padding:32px">' . $body . '</td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">'
            . 'The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · '
            . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8')
            . '</td></tr></table></td></tr></table></body></html>';
    }
}

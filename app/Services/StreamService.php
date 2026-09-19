<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\StreamEvent;

/**
 * The live stream: its settings, who may watch, and the one-viewer rule.
 */
final class StreamService
{
    /** A pass is dropped if its heartbeat stops for this long. */
    public const PASS_WINDOW = 120;

    public static function isLive(): bool
    {
        return Setting::get('stream_enabled', config('stream.enabled') ? '1' : '0') === '1'
            && self::url() !== '';
    }

    public static function url(): string
    {
        $saved = trim(Setting::get('stream_url', ''));

        return $saved !== '' ? $saved : trim((string) config('stream.url'));
    }

    public static function title(): string
    {
        $saved = trim(Setting::get('stream_title', ''));

        return $saved !== '' ? $saved : (string) config('stream.title');
    }

    /** States the watch page can be in when no video is playing. */
    public const HOLDING_STATES = [
        'soon'   => ['label' => 'Starting soon', 'headline' => 'We are about to begin.',
                     'message' => 'The stream starts here automatically the moment we go live. Keep this page open.'],
        'paused' => ['label' => 'Back shortly', 'headline' => 'A short pause.',
                     'message' => 'We will be back in a few minutes. Stay on this page and the stream resumes on its own.'],
        'ended'  => ['label' => 'Ended', 'headline' => 'That is a wrap.',
                     'message' => 'Thank you for joining us. Look out for the recording and the next steps by email.'],
    ];

    /**
     * What the watch page shows when there is no video: a state, a headline,
     * a message, an optional start time to count down to, and an optional
     * "now" line. Organisers edit these from the stream page.
     */
    public static function holding(): array
    {
        $state = Setting::get('stream_state', 'soon');
        if (!isset(self::HOLDING_STATES[$state])) {
            $state = 'soon';
        }
        $defaults = self::HOLDING_STATES[$state];
        $startsAt = trim(Setting::get('stream_starts_at', ''));
        $ts = $startsAt !== '' ? strtotime($startsAt) : false;

        return [
            'live'      => self::isLive(),
            'state'     => $state,
            'label'     => $defaults['label'],
            'headline'  => trim(Setting::get('stream_headline', '')) ?: $defaults['headline'],
            'message'   => trim(Setting::get('stream_message', '')) ?: $defaults['message'],
            'now'       => trim(Setting::get('stream_now', '')),
            'starts_at' => $ts !== false && $state === 'soon' ? $ts : null,
            'starts_text' => $ts !== false && $state === 'soon' ? date('l j F, H:i', $ts) : '',
        ];
    }

    public static function note(): string
    {
        $saved = trim(Setting::get('stream_note', ''));

        return $saved !== '' ? $saved : (string) config('stream.note');
    }

    /**
     * Signed URL on the HLS relay (the standby server's nginx), valid for six hours.
     * Set STREAM_RELAY_URL and STREAM_RELAY_SECRET; the relay holds the real origin.
     */
    public static function relayUrl(): ?string
    {
        $relay = rtrim((string) env('STREAM_RELAY_URL', ''), '/');
        $secret = (string) env('STREAM_RELAY_SECRET', '');
        if ($relay === '' || $secret === '') {
            return null;
        }
        $expires = time() + 6 * 3600;
        $token = rtrim(strtr(base64_encode(md5($secret . $expires, true)), '+/', '-_'), '=');
        $file = basename((string) parse_url(self::url(), PHP_URL_PATH));

        return $relay . '/hls/' . $expires . '/' . $token . '/' . rawurlencode($file);
    }

    public static function proxyEnabled(): bool
    {
        return Setting::get('stream_proxy', config('stream.proxy') ? '1' : '0') === '1';
    }

    /** hls, iframe or file, worked out from the link itself. */
    public static function kind(?string $url = null): string
    {
        $url = $url ?? self::url();
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (str_ends_with($path, '.m3u8')) {
            return 'hls';
        }
        if (preg_match('/youtube\.com|youtu\.be|vimeo\.com|facebook\.com|dailymotion|twitch\.tv/', $host)) {
            return 'iframe';
        }
        if (preg_match('/\.(mp4|webm|ogg)$/', $path)) {
            return 'file';
        }

        return 'iframe';
    }

    /** A watchable embed address for the links that need converting. */
    public static function embedUrl(): string
    {
        $url = self::url();
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (str_contains($host, 'youtu')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $id = $query['v'] ?? trim((string) parse_url($url, PHP_URL_PATH), '/');
            if (str_contains($url, '/embed/') || str_contains($url, '/live/')) {
                return $url;
            }
            return 'https://www.youtube.com/embed/' . rawurlencode($id) . '?autoplay=1&rel=0';
        }

        if (str_contains($host, 'vimeo.com') && !str_contains($url, 'player.vimeo.com')) {
            $id = trim((string) parse_url($url, PHP_URL_PATH), '/');
            return 'https://player.vimeo.com/video/' . rawurlencode($id);
        }

        return $url;
    }

    /** Registrations allowed in, from config. */
    /** The open link admits anyone with an email; an organiser turns it on and off from the dashboard. */
    public static function openLinkOn(): bool
    {
        return Setting::get('watch_open_token', '') !== '';
    }

    public static function openLinkValid(string $token): bool
    {
        $stored = Setting::get('watch_open_token', '');

        return $stored !== '' && hash_equals($stored, $token);
    }

    /**
     * Someone arriving through the open link who is not registered is recorded
     * as an online registrant so they have a name on the board and appear in exports.
     */
    public static function admitGuest(string $email, string $name): ?array
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $existing = Registration::findByEmail($email);
        if ($existing !== null) {
            return $existing['status'] === 'cancelled' ? null : $existing;
        }
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];
        $service = new RegistrationService();
        $registration = $service->register(array_merge(RegistrationService::blank(), [
            'reference'     => $service->generateReference(),
            'participation' => 'online',
            'first_name'    => mb_substr($parts[0] ?? '', 0, 80) ?: 'Guest',
            'last_name'     => mb_substr($parts[1] ?? '', 0, 80) ?: '',
            'email'         => $email,
            'issued_by'     => 'open link',
        ]));
        StreamEvent::log('signin', 'Open link admitted ' . $email);

        return $registration;
    }

    public static function allowedPaths(): array
    {
        return (array) config('stream.allowed');
    }

    /**
     * Find the registration behind what someone typed at the gate.
     * The email address or KingsChat handle is the whole key: references are
     * easily forgotten, so the gate never asks for one.
     */
    public static function findViewer(string $identifier): ?array
    {
        $registration = Registration::findByIdentifier($identifier);
        if ($registration === null) {
            return null;
        }

        return self::mayWatch($registration) ? $registration : null;
    }

    public static function mayWatch(array $registration): bool
    {
        // Someone whose payment is logged but not yet verified by an organiser
        // still gets in. Being locked out on the day is worse than the risk of a
        // false claim, which the one-viewer limit and cancellation already cover.
        return $registration['status'] !== 'cancelled'
            && in_array($registration['participation'], self::allowedPaths(), true)
            && in_array($registration['payment_status'], ['paid', 'not_required', 'claimed'], true);
    }

    /**
     * Claim the single viewing slot for a registration. Any other device
     * holding it is displaced.
     *
     * @return array{takenOver:bool}
     */
    public static function claimPass(string $reference, string $sessionHash, string $device, string $ipHash): array
    {
        $existing = self::pass($reference);
        $takenOver = $existing !== null
            && $existing['session_hash'] !== $sessionHash
            && strtotime((string) $existing['last_seen_at']) > time() - self::PASS_WINDOW;

        $stmt = Database::connection()->prepare(
            'INSERT INTO watch_passes (reference, session_hash, device, ip_hash, takeovers)
             VALUES (:reference, :session, :device, :ip, :takeovers)
             ON DUPLICATE KEY UPDATE
                session_hash = VALUES(session_hash), device = VALUES(device),
                ip_hash = VALUES(ip_hash), issued_at = NOW(), last_seen_at = NOW(),
                takeovers = takeovers + VALUES(takeovers)'
        );
        $stmt->execute([
            'reference' => $reference,
            'session'   => $sessionHash,
            'device'    => $device,
            'ip'        => $ipHash,
            'takeovers' => $takenOver ? 1 : 0,
        ]);

        return ['takenOver' => $takenOver];
    }

    /** True while this session still holds the slot. */
    public static function holdsPass(string $reference, string $sessionHash): bool
    {
        $pass = self::pass($reference);

        return $pass !== null && hash_equals((string) $pass['session_hash'], $sessionHash);
    }

    public static function touchPass(string $reference, string $sessionHash): void
    {
        Database::connection()
            ->prepare('UPDATE watch_passes SET last_seen_at = NOW() WHERE reference = ? AND session_hash = ?')
            ->execute([$reference, $sessionHash]);
    }

    public static function releasePass(string $reference, string $sessionHash): void
    {
        Database::connection()
            ->prepare('DELETE FROM watch_passes WHERE reference = ? AND session_hash = ?')
            ->execute([$reference, $sessionHash]);
    }

    public static function pass(string $reference): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM watch_passes WHERE reference = ? LIMIT 1');
        $stmt->execute([$reference]);

        return $stmt->fetch() ?: null;
    }
}

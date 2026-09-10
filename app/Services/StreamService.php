<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Registration;
use App\Models\Setting;

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

    public static function note(): string
    {
        $saved = trim(Setting::get('stream_note', ''));

        return $saved !== '' ? $saved : (string) config('stream.note');
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
    public static function allowedPaths(): array
    {
        return (array) config('stream.allowed');
    }

    /**
     * Find the registration behind what someone typed at the gate.
     * The reference alone is not enough: it must be paired with the email or
     * the KingsChat handle it was registered with.
     */
    public static function findViewer(string $reference, string $identifier): ?array
    {
        $reference = strtoupper(preg_replace('/\s+/', '', $reference) ?? '');
        $identifier = strtolower(trim($identifier));
        if ($reference === '' || $identifier === '') {
            return null;
        }

        $registration = Registration::findByReference($reference);
        if ($registration === null) {
            return null;
        }

        $matchesEmail = strtolower((string) $registration['email']) === $identifier;
        $handle = ltrim(strtolower((string) ($registration['kingschat_username'] ?? '')), '@');
        $matchesHandle = $handle !== '' && $handle === ltrim($identifier, '@');

        if (!$matchesEmail && !$matchesHandle) {
            return null;
        }

        return self::mayWatch($registration) ? $registration : null;
    }

    public static function mayWatch(array $registration): bool
    {
        return $registration['status'] === 'confirmed'
            && in_array($registration['participation'], self::allowedPaths(), true)
            && in_array($registration['payment_status'], ['paid', 'not_required'], true);
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

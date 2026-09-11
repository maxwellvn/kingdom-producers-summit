<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use RuntimeException;

/**
 * Sends KingsChat messages from the summit account.
 *
 * An organiser authorises the sending account once in a browser; the tokens
 * that come back are kept in settings and refreshed automatically from then on.
 * No account password is ever handled here.
 */
final class KingsChatClient
{
    private const TOKEN_KEY   = 'kingschat_access_token';
    private const REFRESH_KEY = 'kingschat_refresh_token';
    private const EXPIRES_KEY = 'kingschat_expires_at';
    private const SENDER_KEY  = 'kingschat_sender_username';

    /** Refresh a little early so a message never fails on a just-expired token. */
    private const EXPIRY_MARGIN = 120;

    /** Refresh this far ahead of expiry when housekeeping runs, so a quiet site never lapses. */
    private const PROACTIVE_WINDOW = 45 * 60;

    private const LAST_REFRESH_KEY = 'kingschat_last_refresh';
    private const LAST_ERROR_KEY = 'kingschat_last_error';

    public static function isConfigured(): bool
    {
        return (bool) config('kingschat.enabled')
            && trim((string) config('kingschat.client_id')) !== '';
    }

    /** True once an organiser has authorised the sending account. */
    public static function isConnected(): bool
    {
        return self::isConfigured() && Setting::get(self::REFRESH_KEY, '') !== '';
    }

    public static function senderUsername(): string
    {
        $stored = Setting::get(self::SENDER_KEY, '');

        return $stored !== '' ? $stored : (string) config('kingschat.sender');
    }

    /** Where to send an organiser to grant access. */
    public static function authorizeUrl(string $redirectUri): string
    {
        return (string) config('kingschat.endpoints.authorize') . '?' . http_build_query([
            'client_id'     => (string) config('kingschat.client_id'),
            'scopes'        => json_encode(array_values((array) config('kingschat.scopes')), JSON_THROW_ON_ERROR),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'token',
            'post_redirect' => 'true',
        ]);
    }

    /** Store what the authorisation redirect handed back. */
    public static function storeTokens(string $accessToken, string $refreshToken, int $expiresInSeconds): void
    {
        Setting::set(self::TOKEN_KEY, $accessToken);
        if ($refreshToken !== '') {
            Setting::set(self::REFRESH_KEY, $refreshToken);
        }
        Setting::set(self::EXPIRES_KEY, (string) (time() + max(60, $expiresInSeconds)));
    }

    public static function forget(): void
    {
        foreach ([self::TOKEN_KEY, self::REFRESH_KEY, self::EXPIRES_KEY, self::SENDER_KEY] as $key) {
            Setting::set($key, '');
        }
    }

    /**
     * KingsChat returns to the site with a cross-site POST, which carries no
     * session cookie, so the admin session is not available at that moment.
     * The tokens are parked here under a one-time key and collected by the
     * signed-in organiser on the next, same-site request.
     */
    public static function parkHandoff(string $accessToken, string $refreshToken, int $expiresInSeconds): string
    {
        $key = bin2hex(random_bytes(16));
        Setting::set('kingschat_handoff_' . $key, json_encode([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => $expiresInSeconds,
            'created_at'    => time(),
        ], JSON_THROW_ON_ERROR));

        return $key;
    }

    /** Collect a parked authorisation. Single use, and expires in five minutes. */
    public static function claimHandoff(string $key): bool
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
            return false;
        }

        $settingKey = 'kingschat_handoff_' . $key;
        $raw = Setting::get($settingKey, '');
        Setting::set($settingKey, '');

        $parked = json_decode($raw, true);
        if (!is_array($parked) || empty($parked['access_token'])) {
            return false;
        }
        if (time() - (int) ($parked['created_at'] ?? 0) > 300) {
            return false;
        }

        self::storeTokens(
            (string) $parked['access_token'],
            (string) ($parked['refresh_token'] ?? ''),
            (int) ($parked['expires_in'] ?? 3600)
        );

        return true;
    }

    /** A usable access token, refreshed when the stored one is spent. */
    public function accessToken(): ?string
    {
        $token = Setting::get(self::TOKEN_KEY, '');
        $expiresAt = (int) Setting::get(self::EXPIRES_KEY, '0');

        if ($token !== '' && $expiresAt > time() + self::EXPIRY_MARGIN) {
            return $token;
        }

        return $this->refresh();
    }

    public function refresh(): ?string
    {
        $refreshToken = Setting::get(self::REFRESH_KEY, '');
        if ($refreshToken === '' || !self::isConfigured()) {
            self::note(self::LAST_ERROR_KEY, 'Not connected: no refresh token is stored. Connect KingsChat from the admin.');
            return null;
        }

        // Two requests refreshing at once would both spend the same refresh
        // token, and the second would fail. Take a short database lock so the
        // second one waits and then finds a fresh token already stored.
        $pdo = Database::connection();
        $locked = (bool) $pdo->query("SELECT GET_LOCK('kingschat_refresh', 10)")->fetchColumn();
        try {
            if ($locked) {
                $token = Setting::get(self::TOKEN_KEY, '');
                $expiresAt = (int) Setting::get(self::EXPIRES_KEY, '0');
                if ($token !== '' && $expiresAt > time() + self::EXPIRY_MARGIN
                    && Setting::get(self::REFRESH_KEY, '') !== $refreshToken) {
                    return $token; // Someone else just refreshed.
                }
            }

            [$status, $body] = $this->request(
                (string) config('kingschat.endpoints.token'),
                'POST',
                http_build_query([
                    'client_id'     => (string) config('kingschat.client_id'),
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                ]),
                ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']
            );

            $payload = json_decode((string) $body, true);
            if ($status < 200 || $status >= 300 || !is_array($payload) || empty($payload['access_token'])) {
                $why = 'KingsChat token refresh failed with status ' . $status
                    . (is_array($payload) && !empty($payload['error']) ? ' (' . $payload['error'] . ')' : '');
                error_log($why);
                self::note(self::LAST_ERROR_KEY, $why . ' at ' . date('Y-m-d H:i'));
                // A refresh token KingsChat no longer accepts will never work again.
                if (in_array($status, [400, 401], true)) {
                    Setting::set(self::REFRESH_KEY, '');
                    Setting::set(self::TOKEN_KEY, '');
                }
                return null;
            }

            // The API reports the lifetime in milliseconds.
            $seconds = (int) floor(((int) ($payload['expires_in_millis'] ?? 3600000)) / 1000);
            self::storeTokens((string) $payload['access_token'], (string) ($payload['refresh_token'] ?? ''), $seconds);
            self::note(self::LAST_REFRESH_KEY, date('Y-m-d H:i:s'));
            self::note(self::LAST_ERROR_KEY, '');

            return (string) $payload['access_token'];
        } finally {
            if ($locked) {
                $pdo->query("SELECT RELEASE_LOCK('kingschat_refresh')");
            }
        }
    }

    /**
     * Renew the token before it lapses. Called by housekeeping from ordinary
     * traffic, so the token stays fresh without anyone sending a message.
     */
    public static function refreshIfDue(): void
    {
        if (!self::isConnected()) {
            return;
        }
        $expiresAt = (int) Setting::get(self::EXPIRES_KEY, '0');
        if ($expiresAt > time() + self::PROACTIVE_WINDOW) {
            return;
        }
        try {
            (new self())->refresh();
        } catch (\Throwable $e) {
            error_log('KingsChat proactive refresh failed: ' . $e->getMessage());
        }
    }

    /** What the admin page shows: when the token lapses, when it was last renewed, and the last problem. */
    public static function status(): array
    {
        $expiresAt = (int) Setting::get(self::EXPIRES_KEY, '0');

        return [
            'expires_at'   => $expiresAt,
            'expired'      => $expiresAt > 0 && $expiresAt <= time(),
            'last_refresh' => Setting::get(self::LAST_REFRESH_KEY, ''),
            'last_error'   => Setting::get(self::LAST_ERROR_KEY, ''),
            'last_sent'    => Setting::get('kingschat_last_sent', ''),
        ];
    }

    private static function note(string $key, string $value): void
    {
        Setting::set($key, mb_substr($value, 0, 500));
    }

    /**
     * Resolve a username to the user id the message endpoint needs.
     *
     * The directory answers /api/users/{username} for any account, not just
     * contacts, so this reaches anyone. The reply is protobuf rather than JSON,
     * and the user id is the 24-character hex string inside it.
     */
    public function resolveUserId(string $username): ?string
    {
        $username = ltrim(trim($username), '@');
        if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{2,64}$/', $username)) {
            return null;
        }

        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        [$status, $body] = $this->request(
            rtrim((string) config('kingschat.endpoints.user'), '/') . '/' . rawurlencode($username),
            'GET',
            null,
            ['Authorization: Bearer ' . $token, 'Accept: application/json']
        );

        if ($status < 200 || $status >= 300 || $body === false) {
            return null;
        }

        // JSON when the directory offers it, otherwise read the id out of the payload.
        $payload = json_decode((string) $body, true);
        if (is_array($payload)) {
            $id = $payload['user_id'] ?? $payload['id'] ?? $payload['user']['user_id'] ?? null;
            if (is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id)) {
                return $id;
            }
        }

        return preg_match('/[a-f0-9]{24}/i', (string) $body, $matches) === 1 ? $matches[0] : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function contacts(bool $fresh = false): array
    {
        static $cache = null;
        if ($cache !== null && !$fresh) {
            return $cache;
        }

        $token = $this->accessToken();
        if ($token === null) {
            return $cache = [];
        }

        [$status, $body] = $this->request(
            (string) config('kingschat.endpoints.contacts'),
            'GET',
            null,
            ['Authorization: Bearer ' . $token, 'Accept: application/json']
        );

        $payload = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($payload)) {
            return $cache = [];
        }

        return $cache = is_array($payload['contacts'] ?? null) ? $payload['contacts'] : [];
    }

    /**
     * Send a message. Accepts a username or a user id.
     * Returns [sent, reason] so callers can log why nothing went out.
     *
     * @return array{0:bool,1:string}
     */
    public function send(string $recipient, string $text): array
    {
        if (!self::isConnected()) {
            return [false, 'KingsChat is not connected'];
        }
        if (trim($text) === '') {
            return [false, 'Message was empty'];
        }

        $token = $this->accessToken();
        if ($token === null) {
            return [false, 'Could not obtain an access token'];
        }

        // A 24-character hex string is already a user id; anything else is a username.
        $userId = preg_match('/^[a-f0-9]{24}$/i', $recipient) === 1
            ? $recipient
            : $this->resolveUserId($recipient);

        if ($userId === null) {
            return [false, 'No KingsChat user matches "' . $recipient . '"'];
        }

        [$status, $body] = $this->request(
            sprintf((string) config('kingschat.endpoints.message'), rawurlencode($userId)),
            'POST',
            json_encode(['message' => ['body' => ['text' => ['body' => $text]]]], JSON_THROW_ON_ERROR),
            ['Authorization: Bearer ' . $token, 'Content-Type: application/json', 'Accept: application/json']
        );

        if ($status >= 200 && $status < 300) {
            self::note('kingschat_last_sent', date('Y-m-d H:i:s') . ' to @' . ltrim($recipient, '@'));
            return [true, 'sent'];
        }

        // A 401 means the token we hold is no longer accepted: renew and try once more.
        if ($status === 401) {
            $fresh = $this->refresh();
            if ($fresh !== null) {
                [$status, $body] = $this->request(
                    sprintf((string) config('kingschat.endpoints.message'), rawurlencode($userId)),
                    'POST',
                    json_encode(['message' => ['body' => ['text' => ['body' => $text]]]], JSON_THROW_ON_ERROR),
                    ['Authorization: Bearer ' . $fresh, 'Content-Type: application/json', 'Accept: application/json']
                );
                if ($status >= 200 && $status < 300) {
                    self::note('kingschat_last_sent', date('Y-m-d H:i:s') . ' to @' . ltrim($recipient, '@'));
                    return [true, 'sent'];
                }
            }
        }

        $why = 'KingsChat send failed with status ' . $status . ': ' . substr((string) $body, 0, 200);
        error_log($why);
        self::note(self::LAST_ERROR_KEY, 'Send to @' . ltrim($recipient, '@') . ' failed with status ' . $status . ' at ' . date('Y-m-d H:i'));

        return [false, 'KingsChat replied with status ' . $status];
    }

    /**
     * @param string[] $headers
     * @return array{0:int,1:string|false}
     */
    private function request(string $url, string $method, ?string $payload, array $headers): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Could not start a KingsChat request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false) {
            error_log('KingsChat request failed: ' . curl_error($ch));
        }

        return [$status, $body];
    }
}

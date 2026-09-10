<?php

declare(strict_types=1);

namespace App\Services;

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
            return null;
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
            error_log('KingsChat token refresh failed with status ' . $status);
            return null;
        }

        // The API reports the lifetime in milliseconds.
        $seconds = (int) floor(((int) ($payload['expires_in_millis'] ?? 3600000)) / 1000);
        self::storeTokens((string) $payload['access_token'], (string) ($payload['refresh_token'] ?? ''), $seconds);

        return (string) $payload['access_token'];
    }

    /**
     * Resolve a username to the user id the message endpoint needs.
     * The directory has no public username search, so this reads the sending
     * account's own contacts, which is where registrants appear once they have
     * messaged or been added.
     */
    public function resolveUserId(string $username): ?string
    {
        $username = strtolower(ltrim(trim($username), '@'));
        if ($username === '') {
            return null;
        }

        foreach ($this->contacts() as $contact) {
            if (strtolower((string) ($contact['username'] ?? '')) === $username) {
                return (string) ($contact['id'] ?? '') ?: null;
            }
        }

        return null;
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
            return [true, 'sent'];
        }

        error_log('KingsChat send failed with status ' . $status . ': ' . substr((string) $body, 0, 200));

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
        curl_close($ch);

        return [$status, $body];
    }
}

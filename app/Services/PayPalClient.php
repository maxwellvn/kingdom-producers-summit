<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Minimal PayPal REST client (no SDK). Enough for the Orders v2 flow:
 * create an order, capture it after approval, verify webhook signatures.
 */
final class PayPalClient
{
    private string $base;
    private string $clientId;
    private string $secret;

    private static ?string $token = null;
    private static int $tokenExpiresAt = 0;

    public function __construct()
    {
        $this->base = config('paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $this->clientId = (string) config('paypal.client_id');
        $this->secret = (string) config('paypal.secret');
    }

    /** @param array<string,mixed> $body JSON body */
    public function createOrder(array $body): array
    {
        return $this->request('POST', 'v2/checkout/orders', $body);
    }

    /** Charge the payer after they approved the order on PayPal. */
    public function captureOrder(string $orderId): array
    {
        return $this->request('POST', 'v2/checkout/orders/' . rawurlencode($orderId) . '/capture', []);
    }

    public function retrieveOrder(string $orderId): array
    {
        return $this->request('GET', 'v2/checkout/orders/' . rawurlencode($orderId));
    }

    /** Server-side proof the webhook really came from PayPal. */
    public function webhookSignatureValid(array $headers, string $webhookId, array $event): bool
    {
        if ($webhookId === '') {
            return false;
        }

        try {
            $result = $this->request('POST', 'v1/notifications/verify-webhook-signature', [
                'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'] ?? '',
                'cert_url'          => $headers['PAYPAL-CERT-URL'] ?? '',
                'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
                'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
                'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
                'webhook_id'        => $webhookId,
                'webhook_event'     => $event,
            ]);
        } catch (RuntimeException) {
            return false;
        }

        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }

    private function token(): string
    {
        if (self::$token !== null && time() < self::$tokenExpiresAt) {
            return self::$token;
        }

        if ($this->clientId === '' || $this->secret === '') {
            throw new RuntimeException('PayPal client id or secret is not configured.');
        }

        $ch = curl_init($this->base . '/v1/oauth2/token');
        if ($ch === false) {
            throw new RuntimeException('Could not initialise HTTP client.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERPWD        => $this->clientId . ':' . $this->secret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        $data = is_string($body) ? json_decode($body, true) : null;
        if ($status !== 200 || !is_array($data) || !isset($data['access_token'])) {
            throw new RuntimeException('PayPal auth failed (HTTP ' . $status . ')' . ($error !== '' ? ': ' . $error : '.'));
        }

        self::$token = (string) $data['access_token'];
        self::$tokenExpiresAt = time() + max(60, (int) ($data['expires_in'] ?? 300) - 60);

        return self::$token;
    }

    /** @param array<string,mixed> $body */
    private function request(string $method, string $path, array $body = []): array
    {
        $ch = curl_init($this->base . '/' . $path);
        if ($ch === false) {
            throw new RuntimeException('Could not initialise HTTP client.');
        }

        $headers = [
            'Authorization: Bearer ' . $this->token(),
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $method === 'GET' ? null : json_encode($body, JSON_THROW_ON_ERROR),
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            throw new RuntimeException('PayPal returned an unreadable response' . ($error !== '' ? ': ' . $error : '.'));
        }

        if ($status < 200 || $status >= 300) {
            $issue = $data['details'][0]['issue'] ?? $data['message'] ?? 'HTTP ' . $status;
            throw new RuntimeException('PayPal error: ' . $issue);
        }

        return $data;
    }
}

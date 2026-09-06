<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Minimal Stripe REST client (no SDK). Enough for Checkout Sessions.
 */
final class StripeClient
{
    private const API = 'https://api.stripe.com/v1/';

    private string $secret;

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret ?? (string) config('stripe.secret');
    }

    /** @param array<string,string|int> $params form-encoded body */
    public function createCheckoutSession(array $params): array
    {
        return $this->request('POST', 'checkout/sessions', $params);
    }

    public function retrieveSession(string $sessionId): array
    {
        return $this->request('GET', 'checkout/sessions/' . rawurlencode($sessionId), []);
    }

    /** @param array<string,string|int> $params */
    private function request(string $method, string $path, array $params): array
    {
        if ($this->secret === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $ch = curl_init(self::API . $path);
        if ($ch === false) {
            throw new RuntimeException('Could not initialise HTTP client.');
        }

        $headers = [
            'Authorization: Bearer ' . $this->secret,
            'Stripe-Version: 2024-06-20',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Stripe request failed: ' . $error);
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            throw new RuntimeException('Stripe returned an unreadable response.');
        }

        if ($status < 200 || $status >= 300) {
            $message = $data['error']['message'] ?? 'HTTP ' . $status;
            throw new RuntimeException('Stripe error: ' . $message);
        }

        return $data;
    }
}

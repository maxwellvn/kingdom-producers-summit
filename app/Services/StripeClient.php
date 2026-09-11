<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * The little of Stripe this site needs: open a hosted Checkout page, read a
 * session back, and check a webhook signature. Plain curl, no SDK.
 */
final class StripeClient
{
    private const API = 'https://api.stripe.com/v1';

    /** Stripe's tolerance for webhook timestamps, in seconds. */
    private const SIGNATURE_TOLERANCE = 300;

    public static function configured(): bool
    {
        return (bool) config('payments.stripe.enabled')
            && str_starts_with((string) config('payments.stripe.secret'), 'sk_');
    }

    /**
     * Open a Checkout session for one registration and return the URL to send
     * the person to. The registration reference travels as client_reference_id
     * so the webhook and the return page can both find it.
     */
    public static function createCheckout(array $registration, string $successUrl, string $cancelUrl): string
    {
        $amount = price_pence((string) $registration['participation']);
        $label = ['onsite' => 'Onsite place', 'online' => 'Online place'][(string) $registration['participation']] ?? 'Summit place';

        $session = self::request('POST', '/checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => (string) $registration['reference'],
            'customer_email' => (string) $registration['email'],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => (string) config('payments.stripe.currency'),
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][price_data][product_data][name]' => config('app.name') . ' — ' . $label,
            'line_items[0][price_data][product_data][description]' => 'Reference ' . $registration['reference'],
            'metadata[reference]' => (string) $registration['reference'],
            'metadata[participation]' => (string) $registration['participation'],
            'payment_intent_data[description]' => config('app.name') . ' ' . $registration['reference'],
        ], (string) $registration['reference']);

        $url = (string) ($session['url'] ?? '');
        if ($url === '') {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return $url;
    }

    /** Read a Checkout session back, to see whether it was paid. */
    public static function session(string $id): array
    {
        if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $id)) {
            throw new RuntimeException('That is not a Stripe checkout session id.');
        }

        return self::request('GET', '/checkout/sessions/' . $id);
    }

    /**
     * Check a webhook came from Stripe. Returns the decoded event, or null when
     * the signature does not match or is too old.
     */
    public static function verifyWebhook(string $payload, string $signatureHeader): ?array
    {
        $secret = (string) config('payments.stripe.webhook_secret');
        if ($secret === '') {
            return null;
        }

        $timestamp = '';
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }
        if ($timestamp === '' || $signatures === []) {
            return null;
        }
        if (abs(time() - (int) $timestamp) > self::SIGNATURE_TOLERANCE) {
            return null;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                $event = json_decode($payload, true);
                return is_array($event) ? $event : null;
            }
        }

        return null;
    }

    private static function request(string $method, string $path, array $fields = [], string $idempotencyKey = ''): array
    {
        $secret = (string) config('payments.stripe.secret');
        if ($secret === '') {
            throw new RuntimeException('Stripe is not configured.');
        }

        $headers = ['Authorization: Bearer ' . $secret, 'Stripe-Version: 2024-06-20'];
        if ($idempotencyKey !== '') {
            // The same registration retrying gets the same session, not a second charge.
            $headers[] = 'Idempotency-Key: checkout-' . $idempotencyKey . '-' . date('YmdH');
        }

        $ch = curl_init(self::API . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $method === 'POST' ? http_build_query($fields) : null,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Stripe could not be reached: ' . $error);
        }
        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            throw new RuntimeException('Stripe returned something unreadable.');
        }
        if ($status >= 400) {
            $message = (string) ($data['error']['message'] ?? 'Unknown error');
            error_log('Stripe ' . $method . ' ' . $path . ' failed: ' . $message);
            throw new RuntimeException('Stripe refused the request: ' . $message);
        }

        return $data;
    }
}

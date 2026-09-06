<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Url;
use App\Models\Registration;
use RuntimeException;

final class PaymentService
{
    public static function unavailableMessage(): ?string
    {
        if (!filter_var(config('stripe.enabled'), FILTER_VALIDATE_BOOL)
            || trim((string) config('stripe.secret')) === '') {
            return 'Online payment is not available yet. Your registration is saved. Please contact the organisers to complete payment; you do not need to register again.';
        }

        return null;
    }

    /**
     * Create a Stripe Checkout session for an onsite registration and
     * persist the session id. Returns the hosted checkout URL.
     */
    public function startCheckout(array $registration): string
    {
        if (self::unavailableMessage() !== null) {
            throw new RuntimeException('Payments are disabled or the Stripe secret key is not configured.');
        }

        $reference = (string) $registration['reference'];
        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            $base = (string) Url::base();
        }

        $session = (new StripeClient())->createCheckoutSession([
            'mode' => 'payment',
            'success_url' => $base . '/register/paid?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $base . '/register/pay?ref=' . rawurlencode($reference) . '&cancelled=1',
            'customer_email' => (string) $registration['email'],
            'client_reference_id' => $reference,
            'metadata[reference]' => $reference,
            'payment_intent_data[metadata][reference]' => $reference,
            'payment_intent_data[description]' => 'KPS26 onsite registration ' . $reference,
            'line_items[0][quantity]' => '1',
            'line_items[0][price_data][currency]' => (string) config('stripe.currency'),
            'line_items[0][price_data][unit_amount]' => (string) config('stripe.price_pence'),
            'line_items[0][price_data][product_data][name]' => 'Kingdom Producers Summit — onsite registration (London Edition 2026)',
            'line_items[0][price_data][product_data][description]' => 'Discounted place — standard price £50.',
        ]);

        Registration::setStripeSession($reference, (string) $session['id']);

        return (string) $session['url'];
    }

    /**
     * Mark a registration paid and send the confirmation email.
     * Returns false when it was already paid (idempotent for webhook + return URL).
     */
    public function markPaid(string $reference, string $sessionId, int $amountPence = 0): bool
    {
        if ($amountPence <= 0) {
            $amountPence = (int) config('stripe.price_pence');
        }

        $updated = Registration::markPaid($reference, $sessionId, $amountPence);
        if (!$updated) {
            return false;
        }

        $registration = Registration::findByReference($reference);
        if ($registration !== null) {
            try {
                (new RegistrationMail())->send($registration);
            } catch (\Throwable $e) {
                error_log('Registration confirmation email could not be sent: ' . $e->getMessage());
            }
        }

        return true;
    }

    /** Find a pending unpaid onsite registration by reference + email (resume payment). */
    public function findPayable(string $reference, string $email): ?array
    {
        return Registration::findPayable($reference, mb_strtolower(trim($email)));
    }
}

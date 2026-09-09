<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;
use App\Models\Setting;
use RuntimeException;

final class PaymentService
{
    /** The pay methods shown to the registrant, Espees first, in display order. */
    public static function methods(): array
    {
        $amount = number_format((int) config('paypal.price_pence') / 100, 2);

        return [
            'espees' => [
                'label' => 'Espees',
                'blurb' => 'Pay from your Espees wallet.',
                'available' => (bool) config('payments.espees.enabled')
                    && trim((string) config('payments.espees.code')) !== ''
                    && Setting::get('pay_espees_enabled', '1') === '1',
                'href' => 'espees',
            ],
            'paypal' => [
                'label' => 'PayPal',
                'blurb' => "Card or PayPal balance — £{$amount} GBP.",
                'available' => (bool) config('payments.paypal_enabled')
                    && self::unavailableMessage() === null
                    && Setting::get('pay_paypal_enabled', '1') === '1',
                'href' => null, // handled by the checkout POST
            ],
            'bank' => [
                'label' => 'Bank transfer',
                'blurb' => "UK bank transfer of £{$amount} with your reference.",
                'available' => (bool) config('payments.bank.enabled')
                    && trim((string) config('payments.bank.account_name')) !== ''
                    && trim((string) config('payments.bank.account_number')) !== ''
                    && Setting::get('pay_bank_enabled', '1') === '1',
                'href' => 'bank',
            ],
        ];
    }

    /** Only the methods an actual registrant may use right now. */
    public static function availableMethods(): array
    {
        return array_filter(self::methods(), static fn (array $m) => $m['available']);
    }

    public static function unavailableMessage(): ?string
    {
        if (!filter_var(config('paypal.enabled'), FILTER_VALIDATE_BOOL)
            || trim((string) config('paypal.client_id')) === ''
            || trim((string) config('paypal.secret')) === '') {
            return 'Online payment is not available yet. Your registration is saved. Please contact the organisers to complete payment; you do not need to register again.';
        }

        return null;
    }

    /**
     * Create a PayPal order for an onsite registration, persist the order id,
     * and return the approval link to redirect the payer to.
     */
    public function startCheckout(array $registration): string
    {
        if (self::unavailableMessage() !== null) {
            throw new RuntimeException('Payments are disabled or PayPal credentials are not configured.');
        }

        $reference = (string) $registration['reference'];
        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            $base = (string) \App\Core\Url::base();
        }

        $order = (new PayPalClient())->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'custom_id'   => $reference,
                'description' => 'Kingdom Producers Summit — onsite place (London Edition 2026)',
                'amount'      => [
                    'currency_code' => (string) config('paypal.currency'),
                    'value'         => number_format((int) config('paypal.price_pence') / 100, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'brand_name' => 'Kingdom Producers Summit',
                'user_action' => 'PAY_NOW',
                'return_url' => $base . '/register/paid',
                'cancel_url' => $base . '/register/pay?ref=' . rawurlencode($reference) . '&cancelled=1',
            ],
        ]);

        Registration::setPaymentSession($reference, (string) $order['id']);

        foreach ($order['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                return (string) $link['href'];
            }
        }

        throw new RuntimeException('PayPal order created without an approval link.');
    }

    /**
     * Mark a registration paid and send the confirmation email.
     * Returns false when it was already paid (idempotent for webhook + return URL).
     */
    public function markPaid(string $reference, string $sessionId, int $amountPence = 0): bool
    {
        if ($amountPence <= 0) {
            $amountPence = (int) config('paypal.price_pence');
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

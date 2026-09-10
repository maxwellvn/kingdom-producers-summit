<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;
use App\Models\Setting;

final class PaymentService
{
    /** The pay methods shown to the registrant, in display order. */
    public static function methods(int $amountPence = 0): array
    {
        $amountPence = $amountPence ?: price_pence('onsite');
        $amount = number_format($amountPence / 100, 2);

        return [
            'espees' => [
                'label' => 'Espees',
                'blurb' => 'Pay from your Espees wallet.',
                'available' => (bool) config('payments.espees.enabled')
                    && self::espeesCode() !== ''
                    && Setting::get('pay_espees_enabled', '1') === '1',
                'href' => 'espees',
            ],
            'revolut' => [
                'label' => 'Card or bank via Revolut',
                'blurb' => "Pay {$amount} Espees on Revolut's secure checkout page.",
                'available' => (bool) config('payments.revolut.enabled')
                    && self::revolutUrl($amountPence) !== ''
                    && Setting::get('pay_revolut_enabled', '1') === '1',
                'href' => 'revolut',
            ],
        ];
    }

    /** The Espees code, from the admin panel first, then config. */
    public static function espeesCode(): string
    {
        $saved = trim(Setting::get('pay_espees_code', ''));

        return $saved !== '' ? $saved : trim((string) config('payments.espees.code'));
    }

    /**
     * The Revolut checkout link for a given amount. A checkout link is usually
     * fixed-amount, so each price can have its own; a single general link is
     * used when no price-specific one is set.
     */
    public static function revolutUrl(int $amountPence): string
    {
        foreach (['pay_revolut_url_' . $amountPence, 'pay_revolut_url'] as $key) {
            $saved = trim(Setting::get($key, ''));
            if ($saved !== '') {
                return $saved;
            }
        }

        return trim((string) config('payments.revolut.url'));
    }

    /** Only the methods an actual registrant may use right now. */
    public static function availableMethods(int $amountPence = 0): array
    {
        return array_filter(self::methods($amountPence), static fn (array $m) => $m['available']);
    }

    public static function unavailableMessage(): ?string
    {
        if (self::availableMethods() === []) {
            return 'Payment is not available yet. Your registration is saved. Please contact the organisers to complete payment; you do not need to register again.';
        }

        return null;
    }

    /**
     * Mark a registration paid and send the confirmation email.
     * Returns false when it was already paid (idempotent for webhook + return URL).
     */
    public function markPaid(string $reference, string $sessionId, int $amountPence = 0): bool
    {
        if ($amountPence <= 0) {
            $existing = Registration::findByReference($reference);
            $amountPence = price_pence((string) ($existing['participation'] ?? 'onsite'));
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

    /** Find a pending unpaid registration by reference + email (resume payment). */
    public function findPayable(string $reference, string $email): ?array
    {
        return Registration::findPayable($reference, mb_strtolower(trim($email)));
    }
}

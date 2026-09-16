<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Registration messages sent over KingsChat, alongside the email.
 * Every send is best-effort: a failure here must never stop a registration.
 */
final class KingsChatNotifier
{
    /** Confirmation for a registration that needs no payment. */
    public function sendConfirmation(array $registration): array
    {
        $name = trim((string) $registration['first_name']);
        $reference = (string) $registration['reference'];
        $summit = (array) config('app.summit');
        $link = site_url() . '/register/confirmed?access='
            . rawurlencode(AttendanceService::tokenFor($reference));

        $support = '';
        if (is_paid_path((string) $registration['participation']) && ($registration['payment_status'] ?? '') === 'not_required') {
            $support = "If you would like to support the programme, a contribution of "
                . espees_price(price_pence((string) $registration['participation'])) . " is suggested: "
                . site_url() . '/register/method?resume=' . rawurlencode(PaymentService::resumeToken($reference)) . "\n\n";
        }

        $where = '';
        if ((string) $registration['participation'] === 'onsite') {
            $v = (array) config('app.summit.venue');
            $where = "Where: {$v['unit']}, {$v['name']}, {$v['street']}, {$v['town']} {$v['postcode']}\n"
                . 'Get directions: https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode((string) $v['query']) . "\n\n";
        }

        $text = "You are registered, {$name}.\n\n"
            . "Reference: {$reference}\n"
            . "Summit: {$summit['date_text']}, {$summit['city']}\n\n"
            . $where
            . "Your pass and full details: {$link}\n\n"
            . $support
            . 'The Loveworld Consulate, United Kingdom';

        return $this->deliver($registration, $text);
    }

    /** A contribution has been confirmed. */
    public function sendSupportThanks(array $registration): array
    {
        $name = trim((string) $registration['first_name']);
        $text = "Thank you, {$name}. Your " . payment_phrase($registration)
            . " contribution to the Kingdom Producers programme is confirmed.\n\n"
            . "Your place was already confirmed, and nothing about it changes.\n\n"
            . 'The Loveworld Consulate, United Kingdom';

        return $this->deliver($registration, $text);
    }

    /** Their contribution has been logged and is waiting on an organiser. */
    public function sendClaimReceived(array $registration): array
    {
        $name = trim((string) $registration['first_name']);
        $reference = (string) $registration['reference'];
        $paid = payment_phrase($registration);

        $text = "Thank you, {$name}.\n\n"
            . "We have logged your {$paid} contribution for reference {$reference}, "
            . "and we are confirming it now.\n\n"
            . "We confirm it against our own records, so there is nothing for you to send us. "
            . "Your place is already confirmed.\n\n"
            . 'The Loveworld Consulate, United Kingdom';

        return $this->deliver($registration, $text);
    }

    /**
     * @return array{0:bool,1:string} whether it went, and why not if it did not
     */
    private function deliver(array $registration, string $text): array
    {
        $username = trim((string) ($registration['kingschat_username'] ?? ''));
        if ($username === '') {
            return [false, 'No KingsChat username given'];
        }
        if (!KingsChatClient::isConnected()) {
            return [false, 'KingsChat is not connected'];
        }

        try {
            return (new KingsChatClient())->send($username, $text);
        } catch (\Throwable $e) {
            error_log('KingsChat message could not be sent: ' . $e->getMessage());
            return [false, 'KingsChat request failed'];
        }
    }
}

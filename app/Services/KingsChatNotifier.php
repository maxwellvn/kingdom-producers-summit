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

        $text = "You are registered, {$name}.\n\n"
            . "Reference: {$reference}\n"
            . "Summit: {$summit['date_text']}, {$summit['city']}\n\n"
            . "Your pass and full details: {$link}\n\n"
            . 'The Loveworld Consulate, United Kingdom';

        return $this->deliver($registration, $text);
    }

    /** Acknowledgement for a place that is held until payment is confirmed. */
    public function sendPaymentPending(array $registration): array
    {
        $name = trim((string) $registration['first_name']);
        $reference = (string) $registration['reference'];
        $amount = espees_price(price_pence((string) $registration['participation']));
        $link = site_url() . '/register/pay?resume='
            . rawurlencode(PaymentService::resumeToken($reference));

        $text = "Almost there, {$name}.\n\n"
            . "Reference: {$reference}\n"
            . "To pay: {$amount}\n\n"
            . "Complete your payment here: {$link}\n\n"
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

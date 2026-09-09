<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Services\PaymentService;
use App\Services\PayPalClient;
use App\Services\RegistrationMail;
use RuntimeException;

final class PaymentController extends Controller
{
    /** PayPal approval landing: capture the order, mark paid, continue to confirmation. */
    public function paid(Request $request): Response
    {
        $orderId = $request->str('token'); // PayPal appends ?token=<order id>&PayerID=…
        if ($orderId === '') {
            return $this->redirect('/register');
        }

        $client = new PayPalClient();
        try {
            $order = $client->captureOrder($orderId);
        } catch (RuntimeException $e) {
            // A capture can fail for "already captured" or "not approved";
            // fall back to reading the order's actual state before giving up.
            try {
                $order = $client->retrieveOrder($orderId);
            } catch (RuntimeException $e2) {
                error_log('PayPal order lookup failed: ' . $e2->getMessage());
                return $this->redirect('/register/pay?ref=' . rawurlencode($request->str('ref')));
            }
            if (($order['status'] ?? '') !== 'COMPLETED') {
                error_log('PayPal order not completed: ' . $e->getMessage());
                return $this->redirect('/register/pay?ref=' . rawurlencode($request->str('ref')));
            }
        }

        if (($order['status'] ?? '') !== 'COMPLETED') {
            return $this->redirect('/register/pay?ref=' . rawurlencode($request->str('ref')));
        }

        $unit = $order['purchase_units'][0] ?? [];
        $reference = (string) ($unit['custom_id'] ?? '');
        $capture = $unit['payments']['captures'][0] ?? [];
        $amountPence = isset($capture['amount']['value'])
            ? (int) round((float) $capture['amount']['value'] * 100)
            : 0;

        if ($reference === '') {
            return $this->redirect('/register/pay?ref=' . rawurlencode($request->str('ref')));
        }

        (new PaymentService())->markPaid($reference, (string) ($capture['id'] ?? $orderId), $amountPence);

        Session::put('last_registration', $reference);

        return $this->redirect('/register/confirmed');
    }

    /** Method choice after registering (or resuming): Espees first, then PayPal, then bank. */
    public function methodPage(Request $request): Response
    {
        $registration = $this->sessionRegistration(false);
        if ($registration === null) {
            return $this->redirect('/register/pay');
        }
        if ($registration['payment_status'] === 'claimed') {
            return $this->redirect('/register/awaiting'); // already claimed — nothing left to choose
        }

        return $this->view('register/method', [
            'title'     => 'Choose payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'methods'   => PaymentService::availableMethods(),
            'amount'    => number_format((int) config('paypal.price_pence') / 100, 2),
        ]);
    }

    /** Offline instructions: the Espees code or bank details, with their reference. */
    public function instructions(Request $request): Response
    {
        $type = $request->str('type');
        $registration = $this->sessionRegistration(true);
        $methods = PaymentService::methods();

        if ($registration === null || !isset($methods[$type]) || !$methods[$type]['available']) {
            return $this->redirect('/register/method');
        }

        return $this->view('register/instructions', [
            'title'     => 'Payment details — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'type'      => $type,
            'amount'    => number_format((int) config('paypal.price_pence') / 100, 2),
        ]);
    }

    /** "Have you paid?" — the confirmation step before a claim is recorded. */
    public function claimForm(Request $request): Response
    {
        $type = $request->str('type');
        $registration = $this->sessionRegistration(true);
        $methods = PaymentService::methods();

        if ($registration === null || !isset($methods[$type]) || !$methods[$type]['available']) {
            return $this->redirect('/register/method');
        }

        return $this->view('register/claim', [
            'title'     => 'Confirm payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'type'      => $type,
            'amount'    => number_format((int) config('paypal.price_pence') / 100, 2),
        ]);
    }

    /** Record the registrant's payment claim; an admin verifies before the pass is issued. */
    public function claim(Request $request): Response
    {
        $type = $request->str('type');
        $registration = $this->sessionRegistration(true);

        if ($registration === null || !in_array($type, ['espees', 'bank'], true)
            || !Registration::claimPayment((string) $registration['reference'], $type)) {
            return $this->redirect('/register/pay');
        }

        try {
            (new RegistrationMail())->sendClaimReceived(Registration::findByReference((string) $registration['reference']) ?? $registration);
        } catch (\Throwable $e) {
            error_log('Payment claim email could not be sent: ' . $e->getMessage());
        }

        return $this->redirect('/register/awaiting');
    }

    /** Claim received — payment pending organiser confirmation, proof requested. */
    public function awaiting(Request $request): Response
    {
        $registration = $this->sessionRegistration(false);
        if ($registration === null) {
            return $this->redirect('/register/pay');
        }

        return $this->view('register/awaiting', [
            'title'     => 'Payment awaiting confirmation — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'kingschat' => (string) config('payments.proof.kingschat'),
            'proofEmail' => (string) (config('payments.proof.email') ?: config('app.mail.reply_to')),
        ]);
    }

    /** Start PayPal checkout for the session-held registration (from the method page). */
    public function checkout(Request $request): Response
    {
        $registration = $this->sessionRegistration(true);
        if ($registration === null) {
            return $this->redirect('/register/pay');
        }

        try {
            $checkoutUrl = (new PaymentService())->startCheckout($registration);
        } catch (RuntimeException $e) {
            error_log('PayPal checkout failed: ' . $e->getMessage());
            return $this->redirect('/register/pay?ref=' . rawurlencode((string) $registration['reference']));
        }

        return Response::redirect($checkoutUrl);
    }

    /** Resume-payment page (reached via cancel link or after a failed attempt). */
    public function payForm(Request $request): Response
    {
        // Someone who already claimed should see their status, not a payment form.
        $claimed = $this->sessionRegistration(false);
        if ($claimed !== null && $claimed['payment_status'] === 'claimed') {
            return $this->redirect('/register/awaiting');
        }

        $saved = $this->savedRegistration($request->str('ref'));
        return $this->view('register/pay', [
            'title'     => 'Complete payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'reference' => $saved['reference'] ?? $request->str('ref'),
            'email' => $saved['email'] ?? '',
            'savedRegistration' => $saved !== null,
            'cancelled' => $request->str('cancelled') === '1',
            'error'     => $saved !== null ? (PaymentService::unavailableMessage() ?? '') : '',
        ]);
    }

    /** Match a reference + email, then hand the registrant to the method choice. */
    public function payResume(Request $request): Response
    {
        $reference = strtoupper($request->str('reference'));
        // Accept harmless spacing, but never silently replace an incorrect prefix.
        $reference = preg_replace('/\s+/', '', $reference) ?? '';
        $email = mb_strtolower($request->str('email'));

        $saved = $this->savedRegistration($reference);
        if ($saved !== null) {
            $reference = (string) $saved['reference'];
            $email = (string) $saved['email'];
        }

        $registration = $reference !== '' && $email !== ''
            ? (new PaymentService())->findPayable($reference, $email)
            : null;

        if ($registration === null) {
            // Already-claimed registrations resume at the awaiting page, not the pay form.
            $claimed = $reference !== '' ? Registration::findByReference($reference) : null;
            if ($claimed !== null && mb_strtolower((string) $claimed['email']) === $email
                && $claimed['participation'] === 'onsite' && $claimed['payment_status'] === 'claimed') {
                Session::put('last_registration', (string) $claimed['reference']);
                return $this->redirect('/register/awaiting');
            }

            return $this->view('register/pay', $this->payViewData(
                $request->str('reference'),
                'No unpaid onsite registration matches that reference and email. Check your original registration details. If you have already paid, use your confirmation email.',
                $email
            ));
        }

        Session::put('last_registration', (string) $registration['reference']);

        return $this->redirect('/register/method');
    }

    private function payViewData(string $reference, string $error, string $email = ''): array
    {
        return [
            'title'     => 'Complete payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'reference' => $reference,
            'cancelled' => false,
            'email'     => $email,
            'savedRegistration' => $this->savedRegistration($reference) !== null,
            'error'     => $error,
        ];
    }

    /** A pending onsite registration held by the current session. */
    private function sessionRegistration(bool $unpaidOnly = true): ?array
    {
        $reference = Session::get('last_registration');
        if (!is_string($reference) || $reference === '') {
            return null;
        }
        $registration = Registration::findByReference($reference);
        if ($registration === null || $registration['participation'] !== 'onsite'
            || $registration['status'] !== 'pending') {
            return null;
        }
        $statuses = $unpaidOnly ? ['unpaid'] : ['unpaid', 'claimed'];
        if (!in_array($registration['payment_status'], $statuses, true)) {
            return null;
        }
        return $registration;
    }

    /** Only a server-held session may supply registration details without an email lookup. */
    private function savedRegistration(string $requestedReference): ?array
    {
        $reference = Session::get('last_registration');
        if (!is_string($reference) || ($requestedReference !== '' && strtoupper($requestedReference) !== $reference)) {
            return null;
        }
        return $this->sessionRegistration(true);
    }

    /** PayPal webhook: authoritative confirmation for payers who never return to the site. */
    public function webhook(Request $request): Response
    {
        $payload = (string) file_get_contents('php://input');
        $event = json_decode($payload, true);
        $webhookId = (string) config('paypal.webhook_id');

        $headers = [];
        foreach (['PAYPAL-AUTH-ALGO', 'PAYPAL-CERT-URL', 'PAYPAL-TRANSMISSION-ID', 'PAYPAL-TRANSMISSION-SIG', 'PAYPAL-TRANSMISSION-TIME'] as $name) {
            $headers[$name] = (string) ($_SERVER['HTTP_' . str_replace('-', '_', $name)] ?? '');
        }

        if (!is_array($event) || !$this->signatureValid($headers, $webhookId, $event)) {
            return Response::json(['ok' => false], 400);
        }

        if (
            ($event['event_type'] ?? '') === 'PAYMENT.CAPTURE.COMPLETED'
            && ($event['resource']['status'] ?? '') === 'COMPLETED'
        ) {
            $reference = (string) ($event['resource']['custom_id'] ?? '');
            if ($reference !== '') {
                $amountPence = isset($event['resource']['amount']['value'])
                    ? (int) round((float) $event['resource']['amount']['value'] * 100)
                    : 0;
                (new PaymentService())->markPaid($reference, (string) ($event['resource']['id'] ?? ''), $amountPence);
            }
        }

        return Response::json(['ok' => true, 'received' => (string) ($event['event_type'] ?? '')]);
    }

    /** Defer to PayPal's verify-webhook-signature API — no local secret math to get wrong. */
    private function signatureValid(array $headers, string $webhookId, array $event): bool
    {
        if ($webhookId === '') {
            return false;
        }
        return (new PayPalClient())->webhookSignatureValid($headers, $webhookId, $event);
    }
}

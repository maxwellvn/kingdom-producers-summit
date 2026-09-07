<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Services\PaymentService;
use App\Services\StripeClient;
use RuntimeException;

final class PaymentController extends Controller
{
    /** Stripe redirect landing: verify session, mark paid, continue to confirmation. */
    public function paid(Request $request): Response
    {
        $sessionId = $request->str('session_id');
        if ($sessionId === '') {
            return $this->redirect('/register');
        }

        try {
            $session = (new StripeClient())->retrieveSession($sessionId);
        } catch (RuntimeException $e) {
            error_log('Stripe session lookup failed: ' . $e->getMessage());
            return $this->redirect('/register/pay?ref=' . rawurlencode($request->str('ref')));
        }

        $reference = (string) ($session['metadata']['reference'] ?? $session['client_reference_id'] ?? '');

        if (($session['payment_status'] ?? '') !== 'paid' || $reference === '') {
            return $this->redirect('/register/pay?ref=' . rawurlencode($reference));
        }

        (new PaymentService())->markPaid($reference, $sessionId, (int) ($session['amount_total'] ?? 0));

        Session::put('last_registration', $reference);

        return $this->redirect('/register/confirmed');
    }

    /** Resume-payment page (reached via cancel link or after a failed attempt). */
    public function payForm(Request $request): Response
    {
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

    /** Start (or restart) checkout for an unpaid onsite registration. */
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
            return $this->view('register/pay', $this->payViewData(
                $request->str('reference'),
                'No unpaid onsite registration matches that reference and email. Check your original registration details. If you have already paid, use your confirmation email.',
                $email
            ));
        }

        Session::put('last_registration', $reference);

        try {
            $checkoutUrl = (new PaymentService())->startCheckout($registration);
        } catch (RuntimeException $e) {
            error_log('Stripe checkout failed: ' . $e->getMessage());
            return $this->view('register/pay', $this->payViewData(
                $reference,
                PaymentService::unavailableMessage() ?? 'Payment could not be started. Your registration is saved. Please try again in a moment.',
                $email
            ));
        }

        return Response::redirect($checkoutUrl);
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

    /** Only a server-held session may supply registration details without an email lookup. */
    private function savedRegistration(string $requestedReference): ?array
    {
        $reference = Session::get('last_registration');
        if (!is_string($reference) || ($requestedReference !== '' && strtoupper($requestedReference) !== $reference)) {
            return null;
        }
        $registration = Registration::findByReference($reference);
        if ($registration === null || $registration['participation'] !== 'onsite'
            || $registration['payment_status'] !== 'unpaid' || $registration['status'] !== 'pending') {
            return null;
        }
        return $registration;
    }

    /** Stripe webhook: authoritative confirmation for abandoned return URLs. */
    public function webhook(Request $request): Response
    {
        $payload = (string) file_get_contents('php://input');
        $secret = (string) config('stripe.webhook_secret');

        if ($secret === '' || !$this->signatureValid($payload, $secret)) {
            return Response::json(['ok' => false], 400);
        }

        $event = json_decode($payload, true);
        $type = (string) ($event['type'] ?? '');
        $session = $event['data']['object'] ?? [];

        if (
            in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)
            && ($session['payment_status'] ?? '') === 'paid'
        ) {
            $reference = (string) ($session['metadata']['reference'] ?? $session['client_reference_id'] ?? '');
            if ($reference !== '') {
                (new PaymentService())->markPaid($reference, (string) ($session['id'] ?? ''), (int) ($session['amount_total'] ?? 0));
            }
        }

        return Response::json(['ok' => true, 'received' => $type]);
    }

    /** Verify Stripe-Signature (t=…,v1=…) within a 5-minute tolerance. */
    private function signatureValid(string $payload, string $secret): bool
    {
        $header = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            $pair = explode('=', trim($piece), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        $timestamp = (int) ($parts['t'] ?? 0);
        $signature = (string) ($parts['v1'] ?? '');

        if ($timestamp === 0 || $signature === '' || abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        return hash_equals($expected, $signature);
    }
}

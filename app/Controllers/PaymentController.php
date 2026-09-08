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
            error_log('PayPal checkout failed: ' . $e->getMessage());
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

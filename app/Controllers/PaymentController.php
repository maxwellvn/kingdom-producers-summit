<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Services\KingsChatNotifier;
use App\Services\PaymentService;
use App\Services\RegistrationMail;
use App\Services\StripeClient;
use RuntimeException;

final class PaymentController extends Controller
{
    /** Method choice after registering, or after resuming payment. */
    public function methodPage(Request $request): Response
    {
        $registration = $this->sessionRegistration(false, $request);
        if ($registration === null) {
            return $this->redirect('/register/pay?expired=1');
        }
        if ($registration['payment_status'] === 'claimed') {
            // Already claimed, so there is nothing left to choose.
            return $this->redirect('/register/awaiting?resume='
                . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])));
        }

        return $this->view('register/method', [
            'resume'    => PaymentService::resumeToken((string) $registration['reference']),
            'title'     => 'Choose payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'noIndex'   => true,
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'methods'   => PaymentService::availableMethods(price_pence((string) $registration['participation'])),
            'amount'    => number_format(price_pence((string) $registration['participation']) / 100, 2),
            'pence'     => price_pence((string) $registration['participation']),
        ]);
    }

    /** Offline instructions: the Espees code or bank details, with their reference. */
    public function instructions(Request $request): Response
    {
        $type = $request->str('type');
        $registration = $this->sessionRegistration(true, $request);
        if ($registration === null) {
            return $this->redirect('/register/pay?expired=1');
        }
        $methods = PaymentService::methods(price_pence((string) $registration['participation']));
        if (!isset($methods[$type]) || !$methods[$type]['available']) {
            return $this->redirect('/register/method');
        }

        return $this->view('register/instructions', [
            'resume'    => PaymentService::resumeToken((string) $registration['reference']),
            'title'     => 'Payment details — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'noIndex'   => true,
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'type'      => $type,
            'amount'    => number_format(price_pence((string) $registration['participation']) / 100, 2),
            'pence'     => price_pence((string) $registration['participation']),
        ]);
    }

    /** Send the registrant to Stripe's hosted checkout page. */
    public function stripeStart(Request $request): Response
    {
        $registration = $this->sessionRegistration(true, $request);
        if ($registration === null) {
            return $this->redirect('/register/pay?expired=1');
        }
        $methods = PaymentService::methods(price_pence((string) $registration['participation']));
        if (empty($methods['stripe']['available'])) {
            return $this->redirect('/register/method');
        }

        $resume = rawurlencode(PaymentService::resumeToken((string) $registration['reference']));
        $site = rtrim(site_url(), '/');
        try {
            $checkout = StripeClient::createCheckout(
                $registration,
                $site . '/register/stripe/return?session_id={CHECKOUT_SESSION_ID}&resume=' . $resume,
                $site . '/register/pay?cancelled=1&resume=' . $resume
            );
        } catch (\Throwable $e) {
            error_log('Stripe checkout could not be opened: ' . $e->getMessage());
            Session::flash('_errors', ['pay' => 'Card payment is not available right now. Choose another way to pay, or try again in a moment.']);
            return $this->redirect('/register/method?resume=' . $resume);
        }

        return $this->redirect($checkout);
    }

    /**
     * Back from Stripe. The webhook is the source of truth, but checking the
     * session here means the person sees their confirmation straight away
     * rather than waiting for the webhook to arrive.
     */
    public function stripeReturn(Request $request): Response
    {
        $registration = $this->sessionRegistration(false, $request);
        $reference = $registration['reference'] ?? PaymentService::referenceFromResumeToken($request->str('resume'));
        if (!is_string($reference) || $reference === '') {
            return $this->redirect('/register/pay?expired=1');
        }

        $sessionId = $request->str('session_id');
        try {
            $session = StripeClient::session($sessionId);
        } catch (\Throwable $e) {
            error_log('Stripe session could not be read: ' . $e->getMessage());
            Session::put('last_registration', $reference);
            return $this->redirect('/register/method');
        }

        // The session must be for this registration, and actually paid.
        $paidFor = (string) ($session['client_reference_id'] ?? '');
        if ($paidFor !== $reference || ($session['payment_status'] ?? '') !== 'paid') {
            Session::put('last_registration', $reference);
            return $this->redirect('/register/method');
        }

        (new PaymentService())->markPaid($reference, $sessionId, (int) ($session['amount_total'] ?? 0), 'stripe');
        Session::put('last_registration', $reference);

        return $this->redirect('/register/confirmed');
    }

    /** Stripe tells us a checkout was paid. Signed, and safe to receive twice. */
    public function stripeWebhook(Request $request): Response
    {
        $payload = (string) file_get_contents('php://input');
        $event = StripeClient::verifyWebhook($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''));
        if ($event === null) {
            return Response::json(['ok' => false, 'reason' => 'bad_signature'], 400);
        }

        if (($event['type'] ?? '') === 'checkout.session.completed'
            || ($event['type'] ?? '') === 'checkout.session.async_payment_succeeded') {
            $session = (array) ($event['data']['object'] ?? []);
            $reference = (string) ($session['client_reference_id'] ?? '');
            if ($reference !== '' && ($session['payment_status'] ?? '') === 'paid') {
                (new PaymentService())->markPaid(
                    $reference,
                    (string) ($session['id'] ?? 'stripe'),
                    (int) ($session['amount_total'] ?? 0),
                    'stripe'
                );
            }
        }

        return Response::json(['ok' => true]);
    }

    /** "Have you paid?" — the confirmation step before a claim is recorded. */
    public function claimForm(Request $request): Response
    {
        $type = $request->str('type');
        $registration = $this->sessionRegistration(true, $request);
        if ($registration === null) {
            return $this->redirect('/register/pay?expired=1');
        }
        $methods = PaymentService::methods(price_pence((string) $registration['participation']));
        if (!isset($methods[$type]) || !$methods[$type]['available']) {
            return $this->redirect('/register/method');
        }

        return $this->view('register/claim', [
            'resume'    => PaymentService::resumeToken((string) $registration['reference']),
            'title'     => 'Confirm payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'noIndex'   => true,
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'type'      => $type,
            'amount'    => number_format(price_pence((string) $registration['participation']) / 100, 2),
            'pence'     => price_pence((string) $registration['participation']),
        ]);
    }

    /** Record the registrant's payment claim; an admin verifies before the pass is issued. */
    public function claim(Request $request): Response
    {
        $type = $request->str('type');
        $registration = $this->sessionRegistration(true, $request);

        if ($registration === null || !in_array($type, ['espees', 'revolut'], true)
            || !Registration::claimPayment((string) $registration['reference'], $type)) {
            return $this->redirect('/register/pay');
        }

        $claimed = Registration::findByReference((string) $registration['reference']) ?? $registration;

        try {
            (new RegistrationMail())->sendClaimReceived($claimed);
        } catch (\Throwable $e) {
            error_log('Payment claim email could not be sent: ' . $e->getMessage());
        }

        (new KingsChatNotifier())->sendClaimReceived($claimed);

        return $this->redirect('/register/awaiting?resume=' . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])));
    }

    /** Claim received — payment pending organiser confirmation. Organisers verify it themselves. */
    public function awaiting(Request $request): Response
    {
        $registration = $this->sessionRegistration(false, $request);
        if ($registration === null) {
            return $this->redirect('/register/pay?expired=1');
        }

        // Nothing has been claimed yet, so there is nothing to await.
        if ($registration['payment_status'] === 'unpaid') {
            return $this->redirect('/register/method?resume='
                . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])));
        }

        return $this->view('register/awaiting', [
            'resume'    => PaymentService::resumeToken((string) $registration['reference']),
            'title'     => 'Payment awaiting confirmation — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'noIndex'   => true,
            'summit'    => config('app.summit'),
            'registration' => $registration,
        ]);
    }

    /** Resume-payment page (reached via cancel link or after a failed attempt). */
    public function payForm(Request $request): Response
    {
        // Someone who already claimed should see their status, not a payment form.
        $claimed = $this->sessionRegistration(false, $request);
        if ($claimed !== null && $claimed['payment_status'] === 'claimed') {
            return $this->redirect('/register/awaiting?resume='
                . rawurlencode(PaymentService::resumeToken((string) $claimed['reference'])));
        }

        // A signed link from an email or a KingsChat message already identifies
        // them, so send them straight to the payment methods.
        if ($request->str('resume') !== '' && $claimed !== null && $claimed['payment_status'] === 'unpaid') {
            return $this->redirect('/register/method?resume='
                . rawurlencode(PaymentService::resumeToken((string) $claimed['reference'])));
        }

        $saved = $this->savedRegistration($request->str('ref'));
        return $this->view('register/pay', [
            'title'     => 'Complete payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'noIndex'   => true,
            'summit'    => config('app.summit'),
            'reference' => $saved['reference'] ?? $request->str('ref'),
            'identifier' => $saved['email'] ?? '',
            'savedRegistration' => $saved !== null,
            'cancelled' => $request->str('cancelled') === '1',
            'expired'   => $request->str('expired') === '1',
            'error'     => $saved !== null ? (PaymentService::unavailableMessage() ?? '') : '',
        ]);
    }

    /** Identify the registrant by email or KingsChat handle, then hand them to the method choice. */
    public function payResume(Request $request): Response
    {
        $identifier = trim($request->str('identifier'));

        // A resumed session already knows who this is; nothing needs typing.
        $saved = $this->savedRegistration($request->str('reference'));
        if ($saved !== null) {
            $identifier = (string) $saved['email'];
        }

        $registration = $identifier !== '' ? (new PaymentService())->findPayable($identifier) : null;

        if ($registration === null) {
            $known = $identifier !== '' ? Registration::findByIdentifier($identifier) : null;

            // Already-claimed registrations resume at the awaiting page, not the pay form.
            if ($known !== null && $known['payment_status'] === 'claimed') {
                Session::put('last_registration', (string) $known['reference']);
                return $this->redirect('/register/awaiting');
            }

            $message = $known === null
                ? 'We could not find a registration with that email address or KingsChat username. Check what you registered with, or register first.'
                : 'That registration has nothing outstanding to pay. Check your confirmation email for your details.';

            return $this->view('register/pay', $this->payViewData('', $message, $identifier));
        }

        Session::put('last_registration', (string) $registration['reference']);

        return $this->redirect('/register/method');
    }

    private function payViewData(string $reference, string $error, string $identifier = ''): array
    {
        return [
            'title'     => 'Complete payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'noIndex'   => true,
            'summit'    => config('app.summit'),
            'reference' => $reference,
            'cancelled' => false,
            'expired'   => false,
            'identifier' => $identifier,
            'savedRegistration' => $this->savedRegistration($reference) !== null,
            'error'     => $error,
        ];
    }

    /** A pending paid registration held by the current session. */
    private function sessionRegistration(bool $unpaidOnly = true, ?Request $request = null): ?array
    {
        $reference = Session::get('last_registration');

        // Someone who left the site to pay may come back without their session.
        // A signed resume link identifies them without asking for anything.
        if ((!is_string($reference) || $reference === '') && $request !== null) {
            $reference = PaymentService::referenceFromResumeToken($request->str('resume'));
            if ($reference !== null) {
                Session::put('last_registration', $reference);
            }
        }

        if (!is_string($reference) || $reference === '') {
            return null;
        }
        $registration = Registration::findByReference($reference);
        if ($registration === null || !is_paid_path((string) $registration['participation'])
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
}

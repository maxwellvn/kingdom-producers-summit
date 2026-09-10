<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Services\PaymentService;
use App\Services\RegistrationMail;
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
            return $this->redirect('/register/awaiting'); // already claimed — nothing left to choose
        }

        return $this->view('register/method', [
            'resume'    => PaymentService::resumeToken((string) $registration['reference']),
            'title'     => 'Choose payment — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'methods'   => PaymentService::availableMethods(price_pence((string) $registration['participation'])),
            'amount'    => number_format(price_pence((string) $registration['participation']) / 100, 2),
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
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'type'      => $type,
            'amount'    => number_format(price_pence((string) $registration['participation']) / 100, 2),
        ]);
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
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'type'      => $type,
            'amount'    => number_format(price_pence((string) $registration['participation']) / 100, 2),
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

        try {
            (new RegistrationMail())->sendClaimReceived(Registration::findByReference((string) $registration['reference']) ?? $registration);
        } catch (\Throwable $e) {
            error_log('Payment claim email could not be sent: ' . $e->getMessage());
        }

        return $this->redirect('/register/awaiting?resume=' . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])));
    }

    /** Claim received — payment pending organiser confirmation, proof requested. */
    public function awaiting(Request $request): Response
    {
        $registration = $this->sessionRegistration(false, $request);
        if ($registration === null) {
            return $this->redirect('/register/pay?expired=1');
        }

        return $this->view('register/awaiting', [
            'resume'    => PaymentService::resumeToken((string) $registration['reference']),
            'title'     => 'Payment awaiting confirmation — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'summit'    => config('app.summit'),
            'registration' => $registration,
            'kingschat' => (string) config('payments.proof.kingschat'),
            'proofEmail' => (string) (config('payments.proof.email') ?: config('app.mail.reply_to')),
        ]);
    }

    /** Resume-payment page (reached via cancel link or after a failed attempt). */
    public function payForm(Request $request): Response
    {
        // Someone who already claimed should see their status, not a payment form.
        $claimed = $this->sessionRegistration(false, $request);
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
            'expired'   => $request->str('expired') === '1',
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
                && $claimed['payment_status'] === 'claimed') {
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
            'expired'   => false,
            'email'     => $email,
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

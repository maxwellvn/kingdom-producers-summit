<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Setting;
use App\Models\Sponsorship;
use App\Services\PaymentService;
use App\Services\StripeClient;

final class SponsorController extends Controller
{
    public const PRESETS_PENCE = [2500, 5000, 10000, 25000];

    /** Revolut here uses its own open-amount link, so it depends on that being set. */
    private static function methods(): array
    {
        $methods = PaymentService::methods();
        $methods['revolut']['available'] = (bool) config('payments.revolut.enabled')
            && PaymentService::sponsorRevolutUrl() !== ''
            && Setting::get('pay_revolut_enabled', '1') === '1';

        return array_filter($methods, static fn (array $m) => $m['available']);
    }

    public function show(): Response
    {
        return $this->view('sponsor/index', [
            'title'       => 'Sponsor the summit — ' . config('app.name'),
            'bodyClass'   => 'page-register page-sponsor',
            'description' => 'Sponsor the Kingdom Producers Summit by card, Espees or Revolut. Any amount.',
            'summit'      => config('app.summit'),
            'methods'     => self::methods(),
            'espeesCode'  => PaymentService::espeesCode(),
            'presets'     => self::PRESETS_PENCE,
        ]);
    }

    public function start(Request $request): Response
    {
        $methods = self::methods();
        $name = trim($request->str('name'));
        $email = strtolower(trim($request->str('email')));
        $method = $request->str('method');
        $pounds = (float) str_replace([',', '£'], '', $request->str('amount'));
        $pence = (int) round($pounds * 100);

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = 'Please tell us your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'That email address does not look right.';
        }
        if ($pence < Sponsorship::MIN_PENCE || $pence > Sponsorship::MAX_PENCE) {
            $errors['amount'] = 'Choose an amount between £5 and £10,000.';
        }
        if (!isset($methods[$method])) {
            $errors['method'] = 'Choose how you would like to give.';
        }
        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }

        $id = Sponsorship::create([
            'name' => $name,
            'email' => $email,
            'amount_pence' => $pence,
            'method' => $method,
        ]);
        Session::put('sponsorship_id', $id);

        if ($method !== 'stripe') {
            return $this->redirect('/sponsor/send');
        }

        $site = rtrim(site_url(), '/');
        try {
            $checkout = StripeClient::createSponsorCheckout(
                Sponsorship::find($id) ?? [],
                $site . '/sponsor/stripe/return?session_id={CHECKOUT_SESSION_ID}',
                $site . '/sponsor?cancelled=1'
            );
        } catch (\Throwable $e) {
            error_log('Sponsor checkout could not be opened: ' . $e->getMessage());
            return $this->back($request, ['method' => 'Card giving is not available right now. Choose another way, or try again shortly.'], $request->all());
        }

        return $this->redirect($checkout);
    }

    /** Espees or Revolut: show where to send it, then "I have sent it". */
    public function send(): Response
    {
        $s = $this->current();
        if ($s === null || $s['method'] === 'stripe') {
            return $this->redirect('/sponsor');
        }

        return $this->view('sponsor/send', [
            'title'      => 'Send your gift — ' . config('app.name'),
            'bodyClass'  => 'page-register page-sponsor',
            'noIndex'    => true,
            'sponsorship' => $s,
            'espeesCode' => PaymentService::espeesCode(),
            'revolutUrl' => PaymentService::sponsorRevolutUrl(),
        ]);
    }

    public function claim(): Response
    {
        $s = $this->current();
        if ($s === null) {
            return $this->redirect('/sponsor');
        }
        Sponsorship::claim((int) $s['id']);

        return $this->redirect('/sponsor/thanks');
    }

    public function stripeReturn(Request $request): Response
    {
        $s = $this->current();
        if ($s === null) {
            return $this->redirect('/sponsor');
        }
        try {
            $session = StripeClient::session($request->str('session_id'));
        } catch (\Throwable $e) {
            error_log('Sponsor Stripe session could not be read: ' . $e->getMessage());
            return $this->redirect('/sponsor');
        }
        if ((int) ($session['metadata']['sponsorship_id'] ?? 0) !== (int) $s['id'] || ($session['payment_status'] ?? '') !== 'paid') {
            return $this->redirect('/sponsor');
        }
        Sponsorship::markPaid((int) $s['id'], (string) $session['id'], (int) ($session['amount_total'] ?? 0));

        return $this->redirect('/sponsor/thanks');
    }

    public function thanks(): Response
    {
        $s = $this->current();
        if ($s === null || $s['status'] === 'pending') {
            return $this->redirect('/sponsor');
        }

        return $this->view('sponsor/thanks', [
            'title'      => 'Thank you — ' . config('app.name'),
            'bodyClass'  => 'page-confirmed page-sponsor',
            'noIndex'    => true,
            'sponsorship' => $s,
        ]);
    }

    private function current(): ?array
    {
        $id = Session::get('sponsorship_id');

        return is_int($id) ? Sponsorship::find($id) : null;
    }
}

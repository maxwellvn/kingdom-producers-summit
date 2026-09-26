<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\LoginAttempt;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Services\AttendanceService;
use App\Services\PaymentService;
use App\Services\KingsChatNotifier;
use App\Services\RegistrationMail;
use PDOException;

final class RegistrationController extends Controller
{
    public function create(Request $request): Response
    {
        $mode = $request->str('mode');
        if (!in_array($mode, Registration::PARTICIPATION, true)) {
            $mode = '';
        }

        return $this->view('register/create', [
            'title'     => 'Register — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'description' => 'Register for the Kingdom Producers Summit: attend in person in '
                . config('app.summit.city') . ', watch online, or join the initiative.',
            'summit'    => config('app.summit'),
            'mode'      => $mode,
            'fields'    => Registration::FIELDS,
            'stages'    => Registration::STAGES,
            'ageBands'  => Registration::AGE_BANDS,
            'interests' => Registration::INTERESTS,
            'contribute'=> Registration::CONTRIBUTE,
            'hearAbout' => Registration::HEAR_ABOUT,
            'countries' => $this->countries(),
        ]);
    }

    /**
     * No limit on how many register from one connection: whole groups do,
     * from one church network. Scripts are caught by the honeypot and the timer.
     */
    private const MIN_SECONDS_TO_FILL = 4;

    public function store(Request $request): Response
    {
        // A filled honeypot or an instant submit is a script, not a person.
        // Send it back looking like an ordinary error so it learns nothing.
        $opened = (int) $request->str('opened_at');
        if ($request->str('website') !== '' || ($opened > 0 && time() - $opened < self::MIN_SECONDS_TO_FILL)) {
            return $this->back($request, ['email' => 'Please check your details and try again.'], $request->all());
        }

        $service = new RegistrationService();
        [$errors, $clean] = $service->validate($request);

        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }


        try {
            $registration = $service->register($clean);
        } catch (PDOException $e) {
            // 23000 = integrity constraint (duplicate email under race)
            if ($e->getCode() === '23000') {
                return $this->back($request, ['email' => RegistrationService::duplicateMessage($request->str('email')) ?? 'This email is already registered.'], $request->all());
            }
            throw $e;
        }

        Session::put('last_registration', $registration['reference']);

        try {
            (new RegistrationMail())->send($registration);
        } catch (\Throwable $e) {
            error_log('Registration confirmation email could not be sent.');
        }

        (new KingsChatNotifier())->sendConfirmation($registration);

        return $this->redirect('/register/confirmed');
    }

    public function confirmed(Request $request): Response
    {
        $reference = Session::get('last_registration');
        if (!is_string($reference)) {
            $reference = AttendanceService::referenceFromToken($request->str('access'));
        }
        $registration = is_string($reference) ? Registration::findByReference($reference) : null;

        if ($registration === null) {
            return $this->redirect('/register');
        }

        return $this->view('register/confirmed', [
            'title'        => 'You are registered — ' . config('app.name'),
            'noIndex'      => true,
            'bodyClass'    => 'page-confirmed',
            'summit'       => config('app.summit'),
            'registration' => $registration,
            'accessToken'  => AttendanceService::tokenFor($registration['reference']),
            // The optional contribution, offered once they are in.
            'supportUrl'   => is_paid_path((string) $registration['participation']) && $registration['payment_status'] === 'not_required'
                ? url('/register/method?resume=' . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])))
                : null,
        ]);
    }

    /** The desk shortcut: type the email or handle, get the QR on screen. */
    public function passForm(Request $request): Response
    {
        return $this->passView([]);
    }

    public function passLookup(Request $request): Response
    {
        $throttleKey = 'pass|' . $request->ip();
        if (LoginAttempt::lockedForSeconds($throttleKey, 20, 600) > 0) {
            return $this->passView(['auth' => 'Too many attempts. Ask at the desk.']);
        }
        $registration = Registration::findByIdentifier($request->str('identifier'));
        if ($registration === null || $registration['status'] === 'cancelled') {
            LoginAttempt::record($throttleKey);
            usleep(random_int(200_000, 400_000));

            return $this->passView(['auth' => 'That does not match a registration. Check the spelling, or register at the desk.']);
        }
        LoginAttempt::clear($throttleKey);
        Session::put('last_registration', $registration['reference']);

        return $this->redirect('/register/confirmed');
    }

    private function passView(array $errors): Response
    {
        return $this->view('pass/index', [
            'title'     => 'Find my pass — ' . config('app.name'),
            'bodyClass' => 'page-pass',
            'noIndex'   => true,
            'errors'    => $errors,
        ]);
    }

    /** The summit as a calendar file, the same for everyone; no details of the registrant in it. */
    public function calendar(): Response
    {
        $s = config('app.summit');
        if ((string) $s['starts_at'] === '') {
            return $this->redirect('/'); // no date yet, nothing to put in a calendar
        }
        $venue = (string) $s['venue']['query'];
        $stamp = fn (string $iso): string => gmdate('Ymd\THis\Z', strtotime($iso));
        $fold = fn (string $v): string => str_replace(["\\", ";", ",", "\n"], ["\\\\", "\\;", "\\,", "\\n"], $v);
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . $fold((string) $s['organiser']) . '//Summit//EN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:summit-' . md5((string) $s['starts_at'] . site_url()) . '@' . parse_url(site_url(), PHP_URL_HOST),
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            // No public start time yet: an all-day entry, so the calendar does not invent one.
            ...(empty($s['time_hidden'])
                ? ['DTSTART:' . $stamp((string) $s['starts_at']), 'DTEND:' . $stamp((string) $s['ends_at'])]
                : ['DTSTART;VALUE=DATE:' . date('Ymd', strtotime((string) $s['starts_at'])), 'DTEND;VALUE=DATE:' . date('Ymd', strtotime((string) $s['starts_at']) + 86400)]),
            'SUMMARY:' . $fold($s['short'] . ' — ' . $s['edition']),
            'LOCATION:' . $fold($venue),
            'DESCRIPTION:' . $fold('Your pass and details: ' . site_url() . '/register'),
            'URL:' . site_url(),
            'END:VEVENT', 'END:VCALENDAR', '',
        ]);

        return (new Response($ics, 200))
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="producers-summit.ics"');
    }

    /** Re-sends the confirmation email. Always answers the same way, so nobody can probe which emails are registered. */
    public function resend(Request $request): Response
    {
        // ponytail: per-session cap; move to an IP table if abuse shows up in the mail logs.
        $sent = (int) Session::get('resend_count', 0);
        $email = strtolower(trim($request->str('email')));
        if ($sent < 3 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::put('resend_count', $sent + 1);
            $registration = Registration::findByEmail($email);
            if ($registration !== null && $registration['status'] !== 'cancelled') {
                try {
                    (new RegistrationMail())->send($registration);
                } catch (\Throwable $e) {
                    error_log('Confirmation email could not be re-sent.');
                }
            }
        }
        Session::flash('_notice', 'If that email is registered, your pass is on its way. Check spam or promotions too.');

        return $this->redirect('/register#lost-pass');
    }

    /** The attendee's access-pass QR as a PNG (embedded in confirmation emails). */
    public function qr(Request $request): Response
    {
        $token = $request->str('token');

        if (AttendanceService::referenceFromToken($token) === null) {
            return Response::html(\App\Core\View::render('errors/404', ['title' => 'Not found']), 404);
        }

        if (!function_exists('imagecreate')) {
            error_log('QR pass cannot be drawn: the gd extension is missing from this PHP build.');
            return Response::html(\App\Core\View::render('errors/500', ['title' => 'Pass unavailable', 'detail' => '']), 500);
        }

        $old = error_reporting(E_ALL & ~E_DEPRECATED); // vendored phpqrcode predates 8.3 signatures
        require_once BASE_PATH . '/lib/phpqrcode.php';
        ob_start();
        // The vendored lib's trailing colour params became implicitly required in PHP 8.3.
        \QRcode::png($token, false, \QR_ECLEVEL_M, 6, 3, false, 0xFFFFFF, 0x000000);
        $png = (string) ob_get_clean();
        error_reporting($old);

        // Never let a stray notice corrupt the image stream.
        $signature = chr(0x89) . 'PNG';
        if (!str_starts_with($png, $signature) && ($pos = strpos($png, $signature)) !== false) {
            $png = substr($png, $pos);
        }

        return (new Response($png, 200))
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'private, max-age=86400');
    }

    /** @return string[] */
    private function countries(): array
    {
        return [
            'United Kingdom', 'Nigeria', 'Ghana', 'South Africa', 'Kenya', 'United States', 'Canada', 'Ireland',
            'France', 'Germany', 'Netherlands', 'Italy', 'Spain', 'Portugal', 'Belgium', 'Switzerland', 'Sweden',
            'Norway', 'Denmark', 'Finland', 'Poland', 'Austria', 'Greece', 'Turkey', 'United Arab Emirates',
            'Saudi Arabia', 'Qatar', 'India', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Singapore', 'Malaysia',
            'Philippines', 'Indonesia', 'Japan', 'South Korea', 'China', 'Hong Kong', 'Australia', 'New Zealand',
            'Brazil', 'Argentina', 'Mexico', 'Jamaica', 'Trinidad and Tobago', 'Barbados', 'Zimbabwe', 'Zambia',
            'Uganda', 'Tanzania', 'Rwanda', 'Ethiopia', 'Cameroon', 'Côte d\'Ivoire', 'Senegal', 'Sierra Leone',
            'Liberia', 'Gambia', 'Botswana', 'Namibia', 'Malawi', 'Mozambique', 'Angola', 'DR Congo', 'Egypt',
            'Morocco', 'Other',
        ];
    }
}

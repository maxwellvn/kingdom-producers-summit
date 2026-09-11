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

        $capacity = max(1, (int) config('app.summit.onsite_capacity'));

        return $this->view('register/create', [
            'title'     => 'Register — ' . config('app.name'),
            'bodyClass' => 'page-register',
            'description' => 'Register for the Kingdom Producers Summit: attend in person in '
                . config('app.summit.city') . ', watch online, or join the initiative.',
            'summit'    => config('app.summit'),
            'mode'      => $mode,
            'seatsLeft' => max(0, $capacity - Registration::onsiteSeatsTaken()),
            'fields'    => Registration::FIELDS,
            'stages'    => Registration::STAGES,
            'ageBands'  => Registration::AGE_BANDS,
            'interests' => Registration::INTERESTS,
            'contribute'=> Registration::CONTRIBUTE,
            'hearAbout' => Registration::HEAR_ABOUT,
            'countries' => $this->countries(),
        ]);
    }

    /** One address should not be able to fill the room or the mailbox. */
    private const MAX_REGISTRATIONS = 5;
    private const REGISTRATION_WINDOW = 3600;

    public function store(Request $request): Response
    {
        $throttleKey = 'register|' . $request->ip();
        if (LoginAttempt::lockedForSeconds($throttleKey, self::MAX_REGISTRATIONS, self::REGISTRATION_WINDOW) > 0) {
            return $this->back($request, [
                'email' => 'That is several registrations from this connection in a short time. Wait an hour, or contact us if you are registering a group.',
            ], $request->all());
        }

        $service = new RegistrationService();
        [$errors, $clean] = $service->validate($request);

        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }

        LoginAttempt::record($throttleKey);

        try {
            $registration = $service->register($clean);
        } catch (PDOException $e) {
            // 23000 = integrity constraint (duplicate email under race)
            if ($e->getCode() === '23000') {
                return $this->back($request, ['email' => RegistrationService::duplicateMessage($request->str('email')) ?? 'This email is already registered.'], $request->all());
            }
            throw $e;
        }

        // Paid paths save as pending, acknowledge by email, then offer payment methods.
        if (is_paid_path((string) $registration['participation'])) {
            Session::put('last_registration', $registration['reference']);
            try {
                (new RegistrationMail())->sendAcknowledgement($registration);
            } catch (\Throwable $e) {
                error_log('Registration acknowledgement email could not be sent: ' . $e->getMessage());
            }

            (new KingsChatNotifier())->sendPaymentPending($registration);

            return $this->redirect('/register/method');
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

        // Unpaid onsite registrations have no access pass yet; claimed ones await confirmation.
        if ($registration['payment_status'] === 'unpaid') {
            return $this->redirect('/register/pay?resume=' . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])));
        }
        if ($registration['payment_status'] === 'claimed') {
            return $this->redirect('/register/awaiting');
        }

        return $this->view('register/confirmed', [
            'title'        => 'You are registered — ' . config('app.name'),
            'noIndex'      => true,
            'bodyClass'    => 'page-confirmed',
            'summit'       => config('app.summit'),
            'registration' => $registration,
            'accessToken'  => AttendanceService::tokenFor($registration['reference']),
        ]);
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

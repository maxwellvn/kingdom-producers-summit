<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Services\AttendanceService;
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
            'summit'    => config('app.summit'),
            'mode'      => $mode,
            'seatsLeft' => max(0, $capacity - Registration::stats()['onsite']),
            'fields'    => Registration::FIELDS,
            'stages'    => Registration::STAGES,
            'ageBands'  => Registration::AGE_BANDS,
            'interests' => Registration::INTERESTS,
            'contribute'=> Registration::CONTRIBUTE,
            'hearAbout' => Registration::HEAR_ABOUT,
            'countries' => $this->countries(),
        ]);
    }

    public function store(Request $request): Response
    {
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

        // Onsite is a paid path: save as pending, acknowledge by email, then offer payment methods.
        if ($registration['participation'] === 'onsite') {
            Session::put('last_registration', $registration['reference']);
            try {
                (new RegistrationMail())->sendAcknowledgement($registration);
            } catch (\Throwable $e) {
                error_log('Registration acknowledgement email could not be sent: ' . $e->getMessage());
            }

            return $this->redirect('/register/method');
        }

        Session::put('last_registration', $registration['reference']);

        try {
            (new RegistrationMail())->send($registration);
        } catch (\Throwable $e) {
            error_log('Registration confirmation email could not be sent.');
        }

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
        if ($registration['participation'] === 'onsite' && $registration['payment_status'] === 'unpaid') {
            return $this->redirect('/register/pay?ref=' . rawurlencode((string) $registration['reference']));
        }
        if ($registration['participation'] === 'onsite' && $registration['payment_status'] === 'claimed') {
            return $this->redirect('/register/awaiting');
        }

        return $this->view('register/confirmed', [
            'title'        => 'You are registered — ' . config('app.name'),
            'bodyClass'    => 'page-confirmed',
            'summit'       => config('app.summit'),
            'registration' => $registration,
            'accessToken'  => AttendanceService::tokenFor($registration['reference']),
        ]);
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

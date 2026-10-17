<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Events;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\LoginAttempt;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\AttendanceService;

/**
 * Self check-in. The desk shows one QR (admin → Front desk); people scan it with their own phone,
 * check themselves in, and show the welcome screen to the official at the desk.
 * The QR is the plain /checkin link, so a printed copy keeps working; admin opens and closes the page.
 * ponytail: anyone with the link can check in while it is open; the desk still sees each welcome screen.
 */
final class CheckInController extends Controller
{
    public function show(Request $request): Response
    {
        if (!self::allowed()) {
            return $this->page('closed');
        }

        return $this->page('ready', ['person' => self::recognised()]);
    }

    public function store(Request $request): Response
    {
        if (!self::allowed()) {
            return $this->page('closed');
        }

        $registration = $request->str('me') === '1' ? self::recognised() : null;
        if ($registration === null) {
            $throttleKey = 'checkin|' . $request->ip();
            if (LoginAttempt::lockedForSeconds($throttleKey, 20, 600) > 0) {
                return $this->page('ready', ['error' => 'Too many attempts. Please see the official at the desk.']);
            }
            $registration = Registration::findByIdentifier(mb_substr($request->str('identifier'), 0, 190));
            if ($registration === null || $registration['status'] === 'cancelled') {
                LoginAttempt::record($throttleKey);
                usleep(random_int(200_000, 400_000));
                // Carry what they typed into the registration form.
                $typed = mb_substr(trim($request->str('identifier')), 0, 190);
                Session::flash('_old', filter_var($typed, FILTER_VALIDATE_EMAIL) ? ['email' => $typed] : ['kingschat_username' => ltrim($typed, '@')]);

                return $this->page('unregistered', ['identifier' => $typed]);
            }
            LoginAttempt::clear($throttleKey);
        }

        $result = AttendanceService::selfCheckIn($registration, $request->ip());
        if (!$result['ok']) {
            return $this->page('ready', ['error' => $result['message'] . ' Please see the official at the desk.']);
        }

        self::remember((string) $registration['reference'], (string) $result['checked_in_at']);

        return $this->redirect('/checkin/welcome');
    }

    public function welcome(Request $request): Response
    {
        $done = Session::get('checked_in_' . Events::active());
        $registration = is_array($done) ? Registration::findByReference((string) $done['reference']) : null;
        if ($registration === null) {
            return $this->redirect('/checkin');
        }

        return $this->page('welcome', ['person' => $registration, 'checkedInAt' => (string) $done['at']]);
    }

    /**
     * Registered from the check-in page: check them in at once and show the welcome screen.
     * Only the check-in page's register link carries checkin=1; the ordinary form never checks anyone in.
     */
    public static function afterRegistering(Request $request, array $registration): ?string
    {
        $fromCheckIn = $request->str('checkin') === '1' && self::allowed();
        $result = $fromCheckIn ? AttendanceService::selfCheckIn($registration, $request->ip()) : ['ok' => false];
        if (!$result['ok']) {
            return null;
        }
        self::remember((string) $registration['reference'], (string) $result['checked_in_at']);

        return '/checkin/welcome';
    }

    /** Open while self check-in is on for this event in admin → Front desk. */
    public static function allowed(): bool
    {
        return Setting::get('checkin_open', '') === '1';
    }

    private static function remember(string $reference, string $at): void
    {
        Session::put('last_registration', $reference);
        Session::put('checked_in_' . Events::active(), ['reference' => $reference, 'at' => $at]);
    }

    /** The person this phone registered or checked in as before, for one-tap check-in. */
    private static function recognised(): ?array
    {
        $reference = Session::get('last_registration');
        $registration = is_string($reference) ? Registration::findByReference($reference) : null;

        return $registration !== null && $registration['event'] === Events::active() && $registration['status'] !== 'cancelled'
            ? $registration
            : null;
    }

    private function page(string $state, array $data = []): Response
    {
        return $this->view('checkin/index', [
            'title'     => 'Check in — ' . config('app.name'),
            'bodyClass' => 'page-checkin',
            'noIndex'   => true,
            'state'     => $state,
            'summit'    => config('app.summit'),
            ...$data,
        ]);
    }
}

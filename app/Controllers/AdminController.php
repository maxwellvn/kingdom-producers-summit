<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AdminUser;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\AttendanceService;
use App\Services\PaymentService;

final class AdminController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900;

    public function loginForm(Request $request): Response
    {
        if (Session::get('admin_authenticated') === true) {
            return $this->redirect('/admin');
        }

        return $this->view('admin/login', [
            'title'     => 'Admin sign in',
            'bodyClass' => 'page-admin page-admin-login',
        ], 'layouts/admin');
    }

    public function login(Request $request): Response
    {
        $attempts = (int) Session::get('login_attempts', 0);
        $lockedUntil = (int) Session::get('login_locked_until', 0);

        if ($lockedUntil > time()) {
            $mins = (int) ceil(($lockedUntil - time()) / 60);
            return $this->back($request, ['auth' => "Too many attempts. Try again in {$mins} minute(s)."]);
        }

        $email = mb_strtolower($request->str('email'));
        $password = (string) $request->input('password', '');

        $expectedEmail = mb_strtolower((string) config('app.admin.email'));
        $hash = (string) config('app.admin.password_hash');

        $ok = $hash !== ''
            && hash_equals($expectedEmail, $email)
            && password_verify($password, $hash);

        // Additional panel-created admins, alongside the env-configured root.
        if (!$ok) {
            $admin = AdminUser::findByEmail($email);
            $ok = $admin !== null && password_verify($password, (string) $admin['password_hash']);
        }

        if (!$ok) {
            $attempts++;
            Session::put('login_attempts', $attempts);
            if ($attempts >= self::MAX_ATTEMPTS) {
                Session::put('login_locked_until', time() + self::LOCKOUT_SECONDS);
                Session::put('login_attempts', 0);
            }
            usleep(random_int(150_000, 400_000));
            return $this->back($request, ['auth' => 'Those details did not match our records.'], ['email' => $email]);
        }

        Session::regenerate();
        Session::forget('login_attempts');
        Session::forget('login_locked_until');
        Session::put('admin_authenticated', true);
        Session::put('admin_email', $email);

        return $this->redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        Session::destroy();
        return $this->redirect('/admin/login');
    }

    public function admins(Request $request): Response
    {
        return $this->view('admin/admins', [
            'title'     => 'Admin users',
            'admins'    => AdminUser::all(),
            'rootEmail' => (string) config('app.admin.email'),
            'flash'     => (string) Session::get('admin_flash', ''),
        ], 'layouts/admin');
    }

    public function addAdmin(Request $request): Response
    {
        $email = mb_strtolower(trim($request->str('email')));
        $password = (string) $request->input('password', '');
        $rootEmail = mb_strtolower((string) config('app.admin.email'));

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($email === $rootEmail) {
            $errors['email'] = 'That address is the root admin and already has access.';
        } elseif (AdminUser::findByEmail($email) !== null) {
            $errors['email'] = 'That email already has admin access.';
        }
        if (strlen($password) < 10) {
            $errors['password'] = 'Password must be at least 10 characters.';
        }

        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }

        AdminUser::create($email, password_hash($password, PASSWORD_DEFAULT));
        Session::flash('admin_flash', "Admin access added for {$email}.");

        return $this->redirect('/admin/admins');
    }

    public function deleteAdmin(Request $request): Response
    {
        $admin = AdminUser::find((int) $request->input('id', 0));

        if ($admin !== null && mb_strtolower((string) $admin['email']) !== mb_strtolower((string) Session::get('admin_email'))) {
            AdminUser::delete((int) $admin['id']);
        }

        return $this->redirect('/admin/admins');
    }

    public function dashboard(Request $request): Response
    {
        return $this->view('admin/dashboard', [
            'title'     => 'Dashboard',
            'bodyClass' => 'page-admin',
            'stats'     => Registration::stats(),
            'stages'    => Registration::byStage(),
            'recent'    => Registration::paginate(1, 8)['rows'],
            'attendance'=> Registration::attendanceStats(),
        ], 'layouts/admin');
    }

    public function scanner(Request $request): Response
    {
        return $this->view('admin/scanner', [
            'title'      => 'Access scanner',
            'bodyClass'  => 'page-admin page-admin-scanner',
            'attendance' => Registration::attendanceStats(),
        ], 'layouts/admin');
    }

    public function checkIn(Request $request): Response
    {
        $windowStarted = (int) Session::get('scanner_window_started', 0);
        $scanCount = (int) Session::get('scanner_scan_count', 0);
        if ($windowStarted === 0 || time() - $windowStarted >= 60) {
            $windowStarted = time();
            $scanCount = 0;
            Session::put('scanner_window_started', $windowStarted);
        }
        if ($scanCount >= 120) {
            return Response::json(['ok' => false, 'status' => 'invalid', 'message' => 'Scanner limit reached. Wait a moment and try again.'], 429);
        }
        Session::put('scanner_scan_count', $scanCount + 1);

        $token = mb_substr($request->str('token'), 0, 512);
        if ($token === '') {
            return Response::json(['ok' => false, 'status' => 'invalid', 'message' => 'Scan or enter an access code.'], 422);
        }

        if (preg_match('/^KPS26-[A-HJ-NP-Z2-9]{6}$/i', $token)) {
            $token = AttendanceService::tokenFor(strtoupper($token));
        }

        $result = AttendanceService::checkIn(
            $token,
            (string) Session::get('admin_email', 'admin'),
            $request->ip()
        );

        return Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function registrations(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $participation = $request->str('type');
        $search = mb_substr($request->str('q'), 0, 80);

        return $this->view('admin/registrations', [
            'title'         => 'Registrations',
            'bodyClass'     => 'page-admin',
            'result'        => Registration::paginate($page, 25, $participation ?: null, $search),
            'participation' => $participation,
            'search'        => $search,
        ], 'layouts/admin');
    }

    /** Mark an offline payment (Espees / bank claim) as received. Issues the pass by email. */
    public function confirmPayment(Request $request): Response
    {
        $registration = Registration::find((int) $request->input('id', 0));

        if ($registration !== null
            && $registration['participation'] === 'onsite'
            && in_array($registration['payment_status'], ['unpaid', 'claimed'], true)) {
            (new PaymentService())->markPaid(
                (string) $registration['reference'],
                'manual-' . date('Ymd-His'),
                (int) config('paypal.price_pence')
            );
        }

        return $this->redirect('/admin/registrations');
    }

    private const PAYMENT_SETTING_KEYS = [
        'pay_paypal_enabled', 'pay_espees_enabled', 'pay_bank_enabled',
        'pay_espees_code', 'pay_espees_note',
        'pay_bank_account_name', 'pay_bank_number', 'pay_bank_sort_code', 'pay_bank_note',
        'pay_proof_kingschat', 'pay_proof_email',
    ];

    public function paymentSettings(Request $request): Response
    {
        $values = [];
        foreach (self::PAYMENT_SETTING_KEYS as $key) {
            $values[$key] = Setting::get($key, str_ends_with($key, '_enabled') ? '1' : '');
        }

        return $this->view('admin/payment_settings', [
            'title'  => 'Payment methods',
            'values' => $values,
            'flash'  => (string) Session::get('admin_flash', ''),
            'paypalLive' => PaymentService::unavailableMessage() === null,
        ], 'layouts/admin');
    }

    public function savePaymentSettings(Request $request): Response
    {
        foreach (self::PAYMENT_SETTING_KEYS as $key) {
            if (str_ends_with($key, '_enabled')) {
                Setting::set($key, $request->input($key) === '1' ? '1' : '0');
                continue;
            }
            Setting::set($key, mb_substr(trim((string) $request->input($key, '')), 0, 500));
        }

        Session::flash('admin_flash', 'Payment methods updated.');

        return $this->redirect('/admin/payments');
    }

    public function exportCsv(Request $request): Response
    {
        $handle = fopen('php://temp', 'r+');
        $header = null;

        foreach (Registration::all() as $row) {
            unset($row['ip_address'], $row['user_agent']);
            if ($header === null) {
                $header = array_keys($row);
                fputcsv($handle, $header);
            }
            fputcsv($handle, array_map(static fn ($v) => is_string($v) ? self::csvSafe($v) : $v, $row));
        }

        if ($header === null) {
            fputcsv($handle, ['no registrations yet']);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return Response::download("\xEF\xBB\xBF" . $csv, 'producers-summit-registrations-' . date('Ymd-Hi') . '.csv');
    }

    /** Neutralise spreadsheet formula injection. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}

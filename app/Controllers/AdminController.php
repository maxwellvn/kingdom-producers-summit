<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Services\AttendanceService;

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

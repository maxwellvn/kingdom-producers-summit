<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\Analytics;
use App\Models\Comment;
use App\Models\StreamEvent;
use App\Models\LoginAttempt;
use App\Models\Prompt;
use App\Models\Commitment;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\Sponsorship;
use App\Services\Announcer;
use App\Services\BulkSender;
use App\Services\AttendanceService;
use App\Services\StreamService;
use App\Services\KingsChatNotifier;
use App\Services\LoadTester;
use App\Services\RegistrationMail;
use App\Services\RegistrationService;
use App\Services\KingsChatClient;
use App\Services\PaymentService;
use App\Services\SafeUrl;
use PDOException;

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
        $email = mb_strtolower($request->str('email'));
        // Track by IP as well as email so neither a cookie reset nor email cycling clears the count.
        $throttleKeys = array_unique([$request->ip(), $email . '|' . $request->ip()]);

        foreach ($throttleKeys as $key) {
            $lockedFor = LoginAttempt::lockedForSeconds($key, self::MAX_ATTEMPTS, self::LOCKOUT_SECONDS);
            if ($lockedFor > 0) {
                $mins = (int) ceil($lockedFor / 60);
                return $this->back($request, ['auth' => "Too many attempts. Try again in {$mins} minute(s)."], ['email' => $email]);
            }
        }

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
            foreach ($throttleKeys as $key) {
                LoginAttempt::record($key);
            }
            usleep(random_int(150_000, 400_000));
            return $this->back($request, ['auth' => 'Those details did not match our records.'], ['email' => $email]);
        }

        Session::regenerate();
        foreach ($throttleKeys as $key) {
            LoginAttempt::clear($key);
        }
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
            'watchers'  => Analytics::watchers(),
            'express'   => Setting::get('express_registration', '') === '1',
            'openToken' => Setting::get('watch_open_token', ''),
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

        // The registrations page posts a plain form: flash the outcome and go back.
        if (!$request->wantsJson() && str_starts_with($request->str('back'), '/admin/')) {
            Session::flash('admin_flash', $result['message'] ?? ($result['ok'] ? 'Checked in.' : 'Check-in failed.'));

            return $this->redirect($request->str('back'));
        }

        return Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function registrations(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $participation = $request->str('type');
        $search = mb_substr($request->str('q'), 0, 80);
        $support = in_array($request->str('support'), ['contributed', 'legacy'], true) ? $request->str('support') : '';

        return $this->view('admin/registrations', [
            'title'         => 'Registrations',
            'bodyClass'     => 'page-admin',
            'result'        => Registration::paginate($page, 25, $participation ?: null, $search, $support),
            'bulk'          => BulkSender::state(),
            'bulkRunning'   => BulkSender::running(),
            'participation' => $participation,
            'search'        => $search,
            'support'       => $support,
        ], 'layouts/admin');
    }

    /** Send one person their pass or their live link again. */
    public function resend(Request $request): Response
    {
        $registration = Registration::find((int) $request->input('id', 0));
        $what = $request->str('what');
        if ($registration === null || $registration['status'] !== 'confirmed' || !in_array($what, ['pass', 'live'], true)) {
            Session::flash('admin_flash', 'Nothing was sent: that registration is not confirmed.');
            return $this->redirect('/admin/registrations');
        }

        $channel = in_array($request->str('channel'), ['email', 'kingschat', 'both'], true) ? $request->str('channel') : 'both';
        [$emailed, $messaged] = $what === 'pass'
            ? Announcer::sendPass($registration, $channel !== 'kingschat', $channel !== 'email')
            : Announcer::sendLiveLink($registration, $channel !== 'kingschat', $channel !== 'email');
        $name = trim($registration['first_name'] . ' ' . $registration['last_name']);
        Session::flash('admin_flash', ($emailed || $messaged)
            ? ($what === 'pass' ? 'Pass sent again to ' : 'Live link sent to ') . $name . ' by ' . implode(' and ', array_filter([$emailed ? 'email' : '', $messaged ? 'KingsChat' : ''])) . '.'
            : 'Nothing could be sent to ' . $name . '. ' . ($what === 'pass' ? 'Passes go to onsite registrations only.' : 'Check the email address and KingsChat username.'));

        return $this->redirect('/admin/registrations?' . http_build_query(array_filter(['type' => $request->str('type'), 'q' => $request->str('q'), 'page' => $request->str('page')])));
    }

    /** Email every onsite person their pass, or a chosen group their live link, in the background. */
    public function resendAll(Request $request): Response
    {
        $what = $request->str('what');
        if (!in_array($what, ['pass', 'live'], true)) {
            return $this->redirect('/admin/registrations');
        }
        if (BulkSender::running()) {
            Session::flash('admin_flash', 'A bulk send is already running. Wait for it to finish; progress is shown below.');
            return $this->redirect('/admin/registrations#bulk');
        }
        $audience = $what === 'pass' ? 'onsite'
            : (in_array($request->str('audience'), ['online', 'onsite', 'all', 'initiative'], true) ? $request->str('audience') : 'online');
        $by = (string) Session::get('admin_email', 'admin');
        if (!BulkSender::start($what, $audience, $by)) {
            Session::flash('admin_flash', 'The background sender could not be started on this server.');
            return $this->redirect('/admin/registrations');
        }
        StreamEvent::log('stream', ($what === 'pass' ? 'Passes' : 'Live links') . " bulk email started by {$by} ({$audience})");
        Session::flash('admin_flash', ($what === 'pass' ? 'Emailing passes to every onsite person. ' : 'Emailing live links to ' . $audience . ' registrants. ') . 'Progress is shown below; you can leave this page.');

        return $this->redirect('/admin/registrations#bulk');
    }

    public function bulkStatus(Request $request): Response
    {
        return Response::json(['ok' => true, 'running' => BulkSender::running(), 'state' => BulkSender::state()]);
    }

    /** Record a contribution as received. Sends the thank-you. */
    public function confirmPayment(Request $request): Response
    {
        $registration = Registration::find((int) $request->input('id', 0));

        if ($registration !== null
            && is_paid_path((string) $registration['participation'])
            && in_array($registration['payment_status'], ['not_required', 'claimed'], true)) {
            (new PaymentService())->markPaid(
                (string) $registration['reference'],
                'manual-' . date('Ymd-His'),
                price_pence((string) $registration['participation'])
            );
        }

        return $this->redirect('/admin/registrations');
    }

    /** Read-only payment method details (from config/payments.php) + live on/off switches. */
    public function paymentSettings(Request $request): Response
    {
        return $this->view('admin/payment_settings', [
            'title'      => 'Payment methods',
            'flash'      => (string) Session::get('admin_flash', ''),
            'paymentsLive' => PaymentService::unavailableMessage() === null,
            'sponsorships' => Sponsorship::recent(),
            'sponsorTotal' => Sponsorship::totalPaidPence(),
        ], 'layouts/admin');
    }

    /** An Espees or Revolut gift has shown up in the account: mark it paid. */
    public function confirmSponsorship(Request $request): Response
    {
        $s = Sponsorship::find((int) $request->input('id', 0));
        if ($s !== null && $s['status'] !== 'paid') {
            Sponsorship::markPaid((int) $s['id'], 'manual-' . date('Ymd-His'), (int) $s['amount_pence']);
        }

        return $this->redirect('/admin/payments#sponsorships');
    }

    public function savePaymentSettings(Request $request): Response
    {
        foreach (['pay_stripe_enabled', 'pay_espees_enabled', 'pay_revolut_enabled'] as $key) {
            Setting::set($key, $request->input($key) === '1' ? '1' : '0');
        }

        Setting::set('pay_espees_code', mb_substr(trim($request->str('pay_espees_code')), 0, 120));

        $sponsorUrl = trim($request->str('pay_revolut_url_sponsor'));
        if ($sponsorUrl === '' || filter_var($sponsorUrl, FILTER_VALIDATE_URL)) {
            Setting::set('pay_revolut_url_sponsor', $sponsorUrl);
        }

        // A Revolut checkout link is usually fixed-amount, so each price has its own.
        foreach (Registration::PARTICIPATION as $path) {
            $pence = price_pence($path);
            if ($pence <= 0) {
                continue;
            }
            $url = trim($request->str('pay_revolut_url_' . $pence));
            if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                Session::flash('admin_flash', 'That Revolut link is not a valid web address, so it was not saved.');
                return $this->redirect('/admin/payments');
            }
            Setting::set('pay_revolut_url_' . $pence, mb_substr($url, 0, 500));
        }

        Session::flash('admin_flash', 'Payment settings updated.');

        return $this->redirect('/admin/payments');
    }

    /** Permanently remove a registration and its attendance record (e.g. wrong entry, erasure request). */
    /** The watch link to one person, by email, KingsChat, or both. */
    public function deleteRegistration(Request $request): Response
    {
        Registration::delete((int) $request->input('id', 0));

        return $this->redirect('/admin/registrations');
    }

    /** The stream: switch it on, set the link, and tell people. */
    public function stream(Request $request): Response
    {
        if (!LoadTester::running()) {
            LoadTester::cleanup(); // a run that died leaves nothing behind once anyone opens this page
        }

        return $this->view('admin/stream', [
            'title'     => 'Stream',
            'live'      => StreamService::isLive(),
            'url'       => StreamService::url(),
            'kind'      => StreamService::url() !== '' ? StreamService::kind() : '',
            'streamTitle' => StreamService::title(),
            'note'      => StreamService::note(),
            'proxy'     => StreamService::proxyEnabled(),
            'holding'   => StreamService::holding(),
            'holdingStates' => StreamService::HOLDING_STATES,
            'startsAtValue' => trim(Setting::get('stream_starts_at', '')) !== '' ? date('Y-m-d\TH:i', strtotime(Setting::get('stream_starts_at'))) : '',
            'headlineValue' => Setting::get('stream_headline', ''),
            'messageValue'  => Setting::get('stream_message', ''),
            'nowValue'      => Setting::get('stream_now', ''),
            'watchers'  => Analytics::watchers(),
            'loadTest'      => LoadTester::state(),
            'loadTestRunning' => LoadTester::running(),
            'commentsOn'    => Comment::enabled(),
            'prompts'       => Prompt::all(20),
            'commentCount'  => Comment::count(),
            'comments'      => Comment::forModeration(),
            'flash'     => (string) Session::get('admin_flash', ''),
        ], 'layouts/admin');
    }

    /** Simulate a crowd on the watch page and watch the server cope. */
    public function startLoadTest(Request $request): Response
    {
        if (LoadTester::running()) {
            Session::flash('admin_flash', 'A load test is already running. Stop it first.');
            return $this->redirect('/admin/stream#load-test');
        }
        $viewers = (int) $request->str('viewers');
        $seconds = (int) $request->str('seconds');
        StreamEvent::log('loadtest', "Load test started by " . Session::get('admin_email', 'admin') . ": {$viewers} viewers for {$seconds}s");
        LoadTester::start($viewers, $seconds);
        Session::flash('admin_flash', "Load test started: {$viewers} simulated viewers for {$seconds} seconds. Results update below.");

        return $this->redirect('/admin/stream#load-test');
    }

    public function stopLoadTest(Request $request): Response
    {
        LoadTester::stop();
        StreamEvent::log('loadtest', 'Load test stopped by ' . Session::get('admin_email', 'admin'));
        Session::flash('admin_flash', 'Load test stopped and its test viewers removed.');

        return $this->redirect('/admin/stream#load-test');
    }

    public function loadTestStatus(Request $request): Response
    {
        return Response::json(['ok' => true, 'running' => LoadTester::running(), 'state' => LoadTester::state(), 'server' => LoadTester::serverLoad(), 'relay' => StreamService::relayHealth(), 'watching' => count(Analytics::watchers())]);
    }

    /** New log lines since an id, for the live log on the stream page. */
    public function streamLog(Request $request): Response
    {
        $after = (int) $request->str('after');
        $rows = StreamEvent::since($after, $after > 0 ? 200 : 150);

        return Response::json(['ok' => true, 'events' => array_map(static fn (array $r) => [
            'id' => (int) $r['id'],
            'at' => date('H:i:s', strtotime((string) $r['at'])),
            'kind' => (string) $r['kind'],
            'detail' => e((string) $r['detail']),
        ], $rows)]);
    }

    public function clearStreamLog(Request $request): Response
    {
        StreamEvent::clear();
        Session::flash('admin_flash', 'Live log cleared.');

        return $this->redirect('/admin/stream#live-log');
    }

    /** Put a poll or a question to everyone watching. */
    public function createPrompt(Request $request): Response
    {
        $kind = $request->str('kind') === 'poll' ? 'poll' : 'question';
        $question = trim($request->str('question'));
        $options = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $request->str('options'))), static fn ($o) => $o !== ''));
        $options = array_slice($options, 0, Prompt::MAX_OPTIONS);

        if ($question === '') {
            Session::flash('admin_flash', 'Write the question first.');
            return $this->redirect('/admin/stream#prompts');
        }
        if ($kind === 'poll' && count($options) < 2) {
            Session::flash('admin_flash', 'A poll needs at least two options, one per line.');
            return $this->redirect('/admin/stream#prompts');
        }

        // One at a time: opening a new one closes whatever was open.
        foreach (Prompt::all(20) as $existing) {
            if ($existing['status'] === 'open') {
                Prompt::close($existing['id']);
            }
        }
        $id = Prompt::create($kind, $question, $options);
        StreamEvent::log('prompt', ucfirst($kind) . ' posted by ' . Session::get('admin_email', 'admin') . ': ' . mb_substr($question, 0, 100));
        Session::flash('admin_flash', ucfirst($kind) . ' posted. It is on every viewer\'s screen now.');

        return $this->redirect('/admin/stream#prompts');
    }

    public function closePrompt(Request $request): Response
    {
        $id = (int) $request->str('id');
        if ($request->str('delete') === '1') {
            Prompt::delete($id);
            Session::flash('admin_flash', 'Removed, answers included.');
        } else {
            Prompt::close($id);
            StreamEvent::log('prompt', 'Prompt #' . $id . ' closed by ' . Session::get('admin_email', 'admin'));
            Session::flash('admin_flash', 'Closed. Viewers can no longer answer.');
        }

        return $this->redirect('/admin/stream#prompts');
    }

    /** Live results for the admin panel. */
    public function promptResults(Request $request): Response
    {
        $out = [];
        foreach (Prompt::all(20) as $p) {
            $item = ['id' => $p['id'], 'kind' => $p['kind'], 'question' => e((string) $p['question']), 'status' => $p['status'],
                'answers' => (int) $p['answers'], 'options' => array_map(static fn ($o) => e((string) $o), $p['options'])];
            if ($p['kind'] === 'poll') {
                $item['tally'] = Prompt::tally($p);
            } else {
                $item['replies'] = array_map(static fn (array $r) => ['author' => e((string) $r['author']), 'text' => e((string) $r['text']), 'at' => date('H:i', strtotime((string) $r['created_at']))], Prompt::replies($p['id'], 100));
            }
            $out[] = $item;
        }

        return Response::json(['ok' => true, 'prompts' => $out]);
    }

    /**
     * One click from the control bar: go live, pause, end, or reopen as
     * starting soon. Sets the switch and the holding state together so the
     * two can never disagree.
     */
    public function setStreamState(Request $request): Response
    {
        $action = $request->str('action');
        $who = (string) Session::get('admin_email', 'admin');

        // Custom holding words belong to the state they were written for: a
        // "back in five minutes" headline must not survive into Ended.
        $newState = ['live' => 'soon', 'pause' => 'paused', 'end' => 'ended', 'soon' => 'soon'][$action] ?? null;
        if ($newState !== null && $newState !== Setting::get('stream_state', 'soon')) {
            Setting::set('stream_headline', '');
            Setting::set('stream_message', '');
        }

        switch ($action) {
            case 'live':
                if (StreamService::url() === '') {
                    Session::flash('admin_flash', 'Add the stream link under Setup before going live.');
                    return $this->redirect('/admin/stream');
                }
                Setting::set('stream_enabled', '1');
                Setting::set('stream_state', 'soon'); // what they fall back to if the feed drops
                StreamEvent::log('stream', "Went LIVE ({$who})");
                $note = 'You are live. Viewers\' screens switch to the video within fifteen seconds.';
                // Going live emails everyone the link, once per day, so a Resume after a pause does not mail again.
                $lastMailed = (int) Setting::get('live_link_mailed_at', '0');
                if (time() - $lastMailed > 12 * 3600 && !BulkSender::running()) {
                    Setting::set('live_link_mailed_at', (string) time());
                    $note .= BulkSender::start('live', 'all', $who)
                        ? ' The live link is being emailed to everyone; progress is on the Registrations page.'
                        : ' The live link email could not be started; use Email live links on the Registrations page.';
                }
                Session::flash('admin_flash', $note);
                break;
            case 'pause':
                Setting::set('stream_enabled', '0');
                Setting::set('stream_state', 'paused');
                StreamEvent::log('stream', "Paused ({$who})");
                Session::flash('admin_flash', 'Paused. Viewers see "Back shortly". Press Resume when ready.');
                break;
            case 'end':
                Setting::set('stream_enabled', '0');
                Setting::set('stream_state', 'ended');
                StreamEvent::log('stream', "Ended ({$who})");
                Session::flash('admin_flash', 'Ended. Viewers see the closing screen.');
                break;
            case 'soon':
                Setting::set('stream_enabled', '0');
                Setting::set('stream_state', 'soon');
                StreamEvent::log('stream', "Reopened as starting soon ({$who})");
                Session::flash('admin_flash', 'Viewers see "Starting soon" again.');
                break;
        }

        return $this->redirect('/admin/stream');
    }

    /** Open or close the comment board. Closed is the default. */
    /** Event-day switches on the overview: the express form and the open watch link. */
    public function eventDay(Request $request): Response
    {
        $by = (string) Session::get('admin_email', 'admin');
        switch ($request->str('action')) {
            case 'express_on':
            case 'express_off':
                $on = $request->str('action') === 'express_on';
                Setting::set('express_registration', $on ? '1' : '0');
                StreamEvent::log('desk', 'Express registration ' . ($on ? 'on' : 'off') . ' by ' . $by);
                Session::flash('admin_flash', $on
                    ? 'Express registration is on. The form asks for name, contact and consent only.'
                    : 'Express registration is off. The full form is back.');
                break;
            case 'open_link_new':
                Setting::set('watch_open_token', bin2hex(random_bytes(16)));
                StreamEvent::log('desk', 'Open watch link created by ' . $by);
                Session::flash('admin_flash', 'Open watch link ready. Anyone with it gets in with an email address.');
                break;
            case 'open_link_off':
                Setting::set('watch_open_token', '');
                StreamEvent::log('desk', 'Open watch link revoked by ' . $by);
                Session::flash('admin_flash', 'Open watch link revoked. The gate needs a registration again.');
                break;
        }

        return $this->redirect('/admin#event-day');
    }

    public function saveComments(Request $request): Response
    {
        $on = $request->input('comments_enabled') === '1';
        Setting::set('comments_enabled', $on ? '1' : '0');
        StreamEvent::log('comments', 'Comment board ' . ($on ? 'opened' : 'closed') . ' by ' . Session::get('admin_email', 'admin'));

        Session::flash('admin_flash', $on
            ? 'Comments are open. Viewers holding a pass can post.'
            : 'Comments are closed. Nothing new can be posted.');

        return $this->redirect('/admin/stream#comments');
    }

    /** Remove one comment, or clear the whole board. */
    public function deleteComments(Request $request): Response
    {
        if ($request->str('all') === '1') {
            $removed = Comment::clearAll();
            StreamEvent::log('comments', "Board cleared ({$removed} comments) by " . Session::get('admin_email', 'admin'));
            Session::flash('admin_flash', $removed === 0
                ? 'There were no comments to clear.'
                : "Cleared {$removed} comment(s).");

            return $this->redirect('/admin/stream#comments');
        }

        $id = (int) $request->str('id');
        if ($id > 0) {
            Comment::delete($id);
            Session::flash('admin_flash', 'Comment removed.');
        }

        return $this->redirect('/admin/stream#comments');
    }

    public function saveStream(Request $request): Response
    {
        $url = trim($request->str('stream_url'));
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            Session::flash('admin_flash', 'That stream link is not a valid web address, so nothing was saved.');
            return $this->redirect('/admin/stream');
        }
        // The server fetches this link, so it must point at the public web.
        if ($url !== '' && !SafeUrl::isPublicHttp($url)) {
            Session::flash('admin_flash', 'That link points at this machine or a private network, so it was not saved.');
            return $this->redirect('/admin/stream');
        }

        Setting::set('stream_url', mb_substr($url, 0, 500));
        Setting::set('stream_title', mb_substr(trim($request->str('stream_title')), 0, 160));
        Setting::set('stream_note', mb_substr(trim($request->str('stream_note')), 0, 255));
        Setting::set('stream_proxy', $request->input('stream_proxy') === '1' ? '1' : '0');

        // The holding screen: what people see before, between and after.
        $startsAt = trim($request->str('stream_starts_at'));
        Setting::set('stream_starts_at', $startsAt !== '' && strtotime($startsAt) !== false ? date('Y-m-d H:i:s', strtotime($startsAt)) : '');
        Setting::set('stream_headline', mb_substr(trim($request->str('stream_headline')), 0, 120));
        Setting::set('stream_message', mb_substr(trim($request->str('stream_message')), 0, 300));
        Setting::set('stream_now', mb_substr(trim($request->str('stream_now')), 0, 160));

        StreamEvent::log('stream', 'Stream settings saved by ' . Session::get('admin_email', 'admin'));
        Session::flash('admin_flash', 'Settings saved.');

        return $this->redirect('/admin/stream');
    }

    /** Tell a group of registrants something, by email and KingsChat. */
    public function notifications(): Response
    {
        return $this->view('admin/notifications', [
            'title'     => 'Notifications',
            'flash'     => (string) Session::get('admin_flash', ''),
            'audiences' => Announcer::audiences(),
            'templates' => Announcer::TEMPLATES,
            'placeholders' => Announcer::PLACEHOLDERS,
            'counts'    => array_map(
                static fn (string $key) => count(Announcer::recipients($key)),
                array_combine(array_keys(Announcer::audiences()), array_keys(Announcer::audiences()))
            ),
            'queue'     => Announcement::recent(),
            'summitStart' => (string) config('app.summit.starts_at'),
        ], 'layouts/admin');
    }

    /** Send now, or hold until a chosen time; bin/send-due.php sends what is due. */
    public function queueNotification(Request $request): Response
    {
        $audience = $request->str('audience');
        if (!array_key_exists($audience, Announcer::audiences())) {
            Session::flash('admin_flash', 'Choose who the message is for.');
            return $this->redirect('/admin/notifications');
        }

        $template = $request->str('template');
        $subject = trim($request->str('subject'));
        $body = trim($request->str('body'));
        if ($template !== '' && isset(Announcer::TEMPLATES[$template]) && $body === '') {
            $subject = Announcer::TEMPLATES[$template]['subject'];
            $body = Announcer::TEMPLATES[$template]['body'];
        }
        if ($subject === '' || $body === '') {
            Session::flash('admin_flash', 'A message needs a subject and something to say.');
            return $this->redirect('/admin/notifications');
        }

        $when = $request->str('when') === 'later' ? strtotime($request->str('send_at')) : time();
        if ($when === false || $when < time() - 60) {
            Session::flash('admin_flash', 'Pick a time that is still ahead of us.');
            return $this->redirect('/admin/notifications');
        }

        Announcement::create([
            'audience'     => $audience,
            'subject'      => mb_substr($subject, 0, 200),
            'body'         => $body,
            'by_email'     => $request->input('by_email') === '1' ? 1 : 0,
            'by_kingschat' => $request->input('by_kingschat') === '1' ? 1 : 0,
            'send_at'      => date('Y-m-d H:i:s', $when),
            'created_by'   => (string) Session::get('admin_email', ''),
        ]);

        if ($when <= time()) {
            // ponytail: send inline so "now" means now even when the runner is not around.
            require_once BASE_PATH . '/bin/send-due.php';
            Session::flash('admin_flash', 'Sent. See the log below.');
        } else {
            Session::flash('admin_flash', 'Scheduled for ' . date('D j M, H:i', $when) . '.');
        }

        return $this->redirect('/admin/notifications');
    }

    public function cancelNotification(Request $request): Response
    {
        Session::flash('admin_flash', Announcement::cancel((int) $request->input('id', 0)) ? 'Cancelled.' : 'That one had already gone.');

        return $this->redirect('/admin/notifications');
    }

    /** Traffic and who is connected, refreshed live. */
    public function analytics(Request $request): Response
    {
        $days = max(1, min(90, (int) ($request->input('days') ?? 7)));

        return $this->view('admin/analytics', [
            'title'     => 'Analytics',
            'days'      => $days,
            'summary'   => Analytics::summary($days),
            'daily'     => Analytics::daily(min(30, max(7, $days * 2))),
            'pages'     => Analytics::breakdown('path', $days),
            'referrers' => Analytics::breakdown('referrer', $days),
            'devices'   => Analytics::breakdown('device', $days),
            'live'      => Analytics::connectedNow(),
            'watchers'  => Analytics::watchers(),
        ], 'layouts/admin');
    }

    /** The numbers the analytics page polls for, as JSON. */
    public function analyticsLive(Request $request): Response
    {
        $live = Analytics::connectedNow();
        $watchers = array_map(static fn (array $w) => [
            'name'      => trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? '')) ?: 'Guest',
            'email'     => $w['email'] ?? null,
            'reference' => $w['reference'] ?? null,
            'device'    => $w['device'],
            'since'     => $w['started_at'],
        ], Analytics::watchers());

        return Response::json([
            'ok'       => true,
            'live'     => $live,
            'watchers' => $watchers,
            'at'       => gmdate('c'),
        ]);
    }

    /** Issue a place by hand, for guests, speakers and anyone comped. */
    public function issueForm(Request $request): Response
    {

        return $this->view('admin/issue', [
            'title'     => 'Issue a place',
            'flash'     => (string) Session::get('admin_flash', ''),
        ], 'layouts/admin');
    }

    public function issue(Request $request): Response
    {
        $service = new RegistrationService();
        [$errors, $clean] = $service->validateIssued($request, (string) Session::get('admin_email', 'admin'));

        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }

        try {
            $registration = $service->register($clean);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->back($request, ['email' => 'That email is already registered.'], $request->all());
            }
            throw $e;
        }

        $delivered = [];
        try {
            (new RegistrationMail())->send($registration);
            $delivered[] = 'emailed';
        } catch (\Throwable $e) {
            error_log('Issued registration email could not be sent: ' . $e->getMessage());
        }

        [$messaged] = (new KingsChatNotifier())->sendConfirmation($registration);
        if ($messaged) {
            $delivered[] = 'messaged on KingsChat';
        }

        Session::flash('admin_flash', sprintf(
            '%s %s issued to %s%s.',
            ucfirst((string) $registration['participation']),
            'place',
            $registration['email'],
            $delivered ? ' and ' . implode(' and ', $delivered) : ', but nothing could be delivered'
        ));

        return $this->redirect('/admin/issue');
    }

    /** KingsChat: connection status, and the controls to change it. */
    public function kingschat(Request $request): Response
    {
        $handoff = $request->str('handoff');
        if ($handoff !== '') {
            $claimed = KingsChatClient::claimHandoff($handoff);
            Session::flash('admin_flash', $claimed
                ? 'KingsChat is connected.'
                : 'That authorisation link had already been used or had expired. Try connecting again.');

            return $this->redirect('/admin/kingschat');
        }

        return $this->view('admin/kingschat', [
            'title'      => 'KingsChat',
            'configured' => KingsChatClient::isConfigured(),
            'connected'  => KingsChatClient::isConnected(),
            'status'     => KingsChatClient::status(),
            'sender'     => KingsChatClient::senderUsername(),
            'clientId'   => (string) config('kingschat.client_id'),
            'redirect'   => site_url() . '/admin/kingschat/callback',
            'authorize'  => KingsChatClient::authorizeUrl(site_url() . '/admin/kingschat/callback'),
            'flash'      => (string) Session::get('admin_flash', ''),
        ], 'layouts/admin');
    }

    /**
     * Where KingsChat sends the organiser back after they grant access.
     * It arrives as a cross-site POST, so no session cookie comes with it and
     * this route cannot require a signed-in admin. The tokens are parked and
     * collected on the redirect that follows, which is same-site and does.
     */
    public function kingschatReturn(Request $request): Response
    {
        // KingsChat uses camelCase; the snake_case names cover the other flow.
        $accessToken = $request->str('accessToken') ?: $request->str('access_token');
        $refreshToken = $request->str('refreshToken') ?: $request->str('refresh_token');
        $expiresInMillis = (int) ($request->input('expiresInMillis') ?? $request->input('expires_in_millis') ?? 0);

        // Some flows deliver the tokens as a JSON body instead of form fields.
        if ($accessToken === '') {
            $body = json_decode((string) file_get_contents('php://input'), true);
            if (is_array($body)) {
                $accessToken = (string) ($body['accessToken'] ?? $body['access_token'] ?? '');
                $refreshToken = (string) ($body['refreshToken'] ?? $body['refresh_token'] ?? '');
                $expiresInMillis = (int) ($body['expiresInMillis'] ?? $body['expires_in_millis'] ?? 0);
            }
        }

        // Nothing usable yet: the tokens may be in the URL fragment, which only
        // the browser can read, so hand over to a page that forwards them.
        if ($accessToken === '') {
            return $this->view('admin/kingschat_callback', [
                'title'  => 'Connecting KingsChat',
                'target' => url('/admin/kingschat/callback'),
            ], 'layouts/admin');
        }

        $seconds = $expiresInMillis > 0 ? (int) floor($expiresInMillis / 1000) : 3600;
        $handoff = KingsChatClient::parkHandoff($accessToken, $refreshToken, $seconds);

        return $this->redirect('/admin/kingschat?handoff=' . $handoff);
    }

    public function kingschatDisconnect(Request $request): Response
    {
        KingsChatClient::forget();
        Session::flash('admin_flash', 'KingsChat has been disconnected.');

        return $this->redirect('/admin/kingschat');
    }

    /** Send a message to one username, to prove the connection works. */
    public function kingschatTest(Request $request): Response
    {
        $recipient = trim($request->str('recipient'));
        if ($recipient === '') {
            Session::flash('admin_flash', 'Enter a username to send the test to.');
            return $this->redirect('/admin/kingschat');
        }

        [$sent, $reason] = (new KingsChatClient())->send(
            $recipient,
            "Test message from the Kingdom Producers Summit site.\n\nIf you can read this, KingsChat notifications are working."
        );

        Session::flash('admin_flash', $sent
            ? 'Test message sent to ' . $recipient . '.'
            : 'Test message not sent: ' . $reason);

        return $this->redirect('/admin/kingschat');
    }

    public function commitments(Request $request): Response
    {
        return $this->view('admin/commitments', [
            'title'  => 'Commitments',
            'rows'   => Commitment::all(),
            'items'  => Commitment::ITEMS,
            'link'   => site_url() . '/commitment',
        ], 'layouts/admin');
    }

    public function commitmentsCsv(Request $request): Response
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['title', 'first_name', 'last_name', 'email', 'kingschat', 'produce', 'records', 'buy', 'teach', 'made_at']);
        foreach (Commitment::all() as $r) {
            fputcsv($handle, array_map(static fn ($v) => is_string($v) ? self::csvSafe($v) : $v,
                [$r['title'], $r['first_name'], $r['last_name'], $r['email'], $r['kingschat'], $r['produce'], $r['records'], $r['buy'], $r['teach'], $r['created_at']]));
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return Response::download("\xEF\xBB\xBF" . $csv, 'producers-summit-commitments-' . date('Ymd-Hi') . '.csv');
    }

    /** A QR pointing at the commitment page, for the screen or a printed card. */
    public function commitmentsQr(Request $request): Response
    {
        $old = error_reporting(E_ALL & ~E_DEPRECATED);
        require_once BASE_PATH . '/lib/phpqrcode.php';
        ob_start();
        \QRcode::png(site_url() . '/commitment', false, \QR_ECLEVEL_M, 12, 2, false, 0xFFFFFF, 0x000000);
        $png = (string) ob_get_clean();
        error_reporting($old);
        $pos = strpos($png, chr(0x89) . 'PNG');
        $png = $pos === false ? '' : substr($png, $pos);

        return (new Response($png))->header('Content-Type', 'image/png')->header('Content-Disposition', 'inline; filename="commitment-qr.png"');
    }

    public function exportCsv(Request $request): Response
    {
        $handle = fopen('php://temp', 'r+');
        $header = null;
        $type = $request->str('type');

        foreach (Registration::all($type) as $row) {
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

        $suffix = in_array($type, Registration::PARTICIPATION, true) ? '-' . $type : '';
        return Response::download("\xEF\xBB\xBF" . $csv, 'producers-summit-registrations' . $suffix . '-' . date('Ymd-Hi') . '.csv');
    }

    /** Neutralise spreadsheet formula injection. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}

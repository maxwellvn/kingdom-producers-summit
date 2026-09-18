<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Analytics;
use App\Models\Comment;
use App\Models\StreamEvent;
use App\Models\LoginAttempt;
use App\Models\Prompt;
use App\Services\SafeUrl;
use App\Services\AttendanceService;
use App\Services\StreamService;
use App\Services\VisitorTracker;

/** The protected stream page: the gate, the player, and the one-viewer rule. */
final class WatchController extends Controller
{
    private const SESSION_KEY = 'watch_reference';

    /** Wrong guesses allowed at the gate before it closes for a while. */
    private const MAX_ATTEMPTS = 8;
    private const LOCKOUT_SECONDS = 900;

    /** A single stream file should never be larger than this. */
    private const MAX_FETCH_BYTES = 24 * 1024 * 1024;

    public function show(Request $request): Response
    {
        $organiser = $this->organiser();
        if ($organiser !== null) {
            if (Session::get('organiser_watch_logged') !== true) {
                Session::put('organiser_watch_logged', true);
                StreamEvent::log('signin', 'Organiser ' . $organiser['email'] . ' opened the watch page');
            }
            return $this->player($organiser);
        }

        // A signed link from an organiser's message signs the person straight in.
        $passRef = AttendanceService::referenceFromToken($request->str('pass'));
        if ($passRef !== null) {
            $holder = \App\Models\Registration::findByReference($passRef);
            if ($holder !== null && StreamService::mayWatch($holder)) {
                StreamService::claimPass(
                    $passRef,
                    VisitorTracker::sessionHash(),
                    Analytics::device($request->userAgent()),
                    substr(hash_hmac('sha256', $request->ip(), (string) config('app.key')), 0, 32)
                );
                Session::put(self::SESSION_KEY, $passRef);
            }

            return $this->redirect('/watch'); // drop the token from the address bar
        }

        $reference = Session::get(self::SESSION_KEY);
        $viewer = is_string($reference) ? \App\Models\Registration::findByReference($reference) : null;

        if ($viewer !== null && !StreamService::mayWatch($viewer)) {
            $viewer = null;
            Session::forget(self::SESSION_KEY);
        }

        // A pass taken by another device ends this one.
        if ($viewer !== null && !StreamService::holdsPass((string) $viewer['reference'], VisitorTracker::sessionHash())) {
            Session::forget(self::SESSION_KEY);

            return $this->gate($request, ['auth' => 'Your pass was opened on another device, so this one was signed out. Sign in again to take it back.']);
        }

        if ($viewer === null) {
            return $this->gate($request);
        }

        StreamService::touchPass((string) $viewer['reference'], VisitorTracker::sessionHash());
        Analytics::touch(
            VisitorTracker::sessionHash(),
            Analytics::visitorHash($request->ip(), $request->userAgent()),
            '/watch',
            Analytics::device($request->userAgent()),
            'watch',
            (string) $viewer['reference']
        );

        return $this->player($viewer);
    }

    private function player(array $viewer): Response
    {
        return $this->view('watch/player', [
            'title'     => StreamService::title(),
            'bodyClass' => 'page-watch',
            'noIndex'   => true,
            'presenceContext' => 'watch',
            'viewer'    => $viewer,
            'live'      => StreamService::isLive(),
            'kind'      => StreamService::kind(),
            'note'      => StreamService::note(),
            'holding'   => StreamService::holding(),
            'commentsOn' => Comment::enabled(),
            'summit'    => config('app.summit'),
        ]);
    }

    /** Check what was typed at the gate and hand over the single pass. */
    public function enter(Request $request): Response
    {
        // A reference is short, so guessing has to be made expensive.
        $throttleKey = 'watch|' . $request->ip();
        $lockedFor = LoginAttempt::lockedForSeconds($throttleKey, self::MAX_ATTEMPTS, self::LOCKOUT_SECONDS);
        if ($lockedFor > 0) {
            $minutes = (int) ceil($lockedFor / 60);

            return $this->gate($request, ['auth' => "Too many attempts. Try again in {$minutes} minute(s)."]);
        }

        $identifier = $request->str('identifier');

        if ($identifier === '') {
            return $this->gate($request, ['auth' => 'Enter the email address or KingsChat username you registered with.']);
        }

        $viewer = StreamService::findViewer($identifier);
        if ($viewer === null) {
            StreamEvent::log('refused', 'Gate refused "' . mb_substr($identifier, 0, 60) . '"');
            LoginAttempt::record($throttleKey);
            usleep(random_int(200_000, 500_000)); // Slow down guessing.

            return $this->gate($request, ['auth' => 'That does not match a confirmed registration for this summit. Check the email address or KingsChat username you registered with.']);
        }

        LoginAttempt::clear($throttleKey);

        $session = VisitorTracker::sessionHash();
        $claim = StreamService::claimPass(
            (string) $viewer['reference'],
            $session,
            Analytics::device($request->userAgent()),
            substr(hash_hmac('sha256', $request->ip(), (string) config('app.key')), 0, 32)
        );

        Session::put(self::SESSION_KEY, (string) $viewer['reference']);
        $who = trim($viewer['first_name'] . ' ' . $viewer['last_name']) . ' (' . $viewer['reference'] . ', ' . $viewer['participation'] . ')';
        if ($claim['takenOver']) {
            StreamEvent::log('takeover', $who . ' signed in on a new device; the old one was signed out');
            Session::flash('watch_notice', 'You were watching on another device. That one has been signed out.');
        } else {
            StreamEvent::log('signin', $who . ' signed in on ' . Analytics::device($request->userAgent()));
        }

        return $this->redirect('/watch');
    }

    public function leave(Request $request): Response
    {
        $reference = Session::get(self::SESSION_KEY);
        if (is_string($reference)) {
            StreamEvent::log('signout', $reference . ' signed out');
            StreamService::releasePass($reference, VisitorTracker::sessionHash());
        }
        Session::forget(self::SESSION_KEY);
        Analytics::leave(VisitorTracker::sessionHash());

        return $this->redirect('/watch');
    }

    /**
     * The player asks for the stream here rather than reading it from the
     * page, so the address never appears in the markup.
     */
    public function source(Request $request): Response
    {
        $viewer = $this->currentViewer();
        if ($viewer === null) {
            return Response::json(['ok' => false, 'reason' => 'signed_out'], 403);
        }
        if (!$this->holds($viewer)) {
            return Response::json(['ok' => false, 'reason' => 'taken_over'], 409);
        }
        if (!StreamService::isLive()) {
            return Response::json(['ok' => true, 'live' => false, 'holding' => StreamService::holding(), 'watching' => Analytics::watchingCount()]);
        }
        $holding = StreamService::holding();

        $kind = StreamService::kind();
        $source = $kind === 'hls' && StreamService::proxyEnabled()
            ? url('/watch/hls?file=' . rawurlencode(basename((string) parse_url(StreamService::url(), PHP_URL_PATH))))
            : ($kind === 'iframe' ? StreamService::embedUrl() : StreamService::url());

        return Response::json(['ok' => true, 'live' => true, 'kind' => $kind, 'source' => $source, 'now' => $holding['now'], 'watching' => Analytics::watchingCount()]);
    }

    /** Heartbeat: keeps the pass alive and reports when it has been taken. */
    public function beat(Request $request): Response
    {
        $viewer = $this->currentViewer();
        if ($viewer === null) {
            return Response::json(['ok' => false, 'reason' => 'signed_out'], 403);
        }

        $session = VisitorTracker::sessionHash();
        if (!$this->holds($viewer)) {
            return Response::json(['ok' => false, 'reason' => 'taken_over'], 409);
        }

        if (empty($viewer['is_organiser'])) {
            StreamService::touchPass((string) $viewer['reference'], $session);
        }
        Analytics::touch(
            $session,
            Analytics::visitorHash($request->ip(), $request->userAgent()),
            '/watch',
            Analytics::device($request->userAgent()),
            'watch',
            (string) $viewer['reference']
        );

        return Response::json(['ok' => true, 'live' => StreamService::isLive()]);
    }

    /** The comment board, for viewers holding a pass. */
    public function comments(Request $request): Response
    {
        $viewer = $this->passHolder();
        if ($viewer === null) {
            return Response::json(['ok' => false, 'reason' => 'signed_out'], 403);
        }
        // The organisers' poll or question rides along with the comment poll, so
        // it pops up within seconds whether or not the board is open.
        $prompt = self::promptFor($viewer);
        if (!Comment::enabled()) {
            return Response::json(['ok' => true, 'enabled' => false, 'comments' => [], 'prompt' => $prompt, 'watching' => Analytics::watchingCount()]);
        }

        return Response::json([
            'ok' => true,
            'enabled' => true,
            'comments' => self::present(Comment::recent(100, (int) $request->str('after'))),
            'watching' => Analytics::watchingCount(),
            'prompt' => $prompt,
        ]);
    }

    /** Leave a comment. Only while an organiser has the board switched on. */
    public function postComment(Request $request): Response
    {
        $viewer = $this->passHolder();
        if ($viewer === null) {
            return Response::json(['ok' => false, 'reason' => 'signed_out'], 403);
        }
        if (!Comment::enabled()) {
            return Response::json(['ok' => false, 'reason' => 'closed',
                'message' => 'Comments are closed.'], 403);
        }

        $body = trim($request->str('body'));
        if ($body === '') {
            return Response::json(['ok' => false, 'reason' => 'empty',
                'message' => 'Write something first.'], 422);
        }
        if (mb_strlen($body) > Comment::MAX_LENGTH) {
            return Response::json(['ok' => false, 'reason' => 'too_long',
                'message' => 'Comments are limited to ' . Comment::MAX_LENGTH . ' characters.'], 422);
        }

        // One comment every few seconds, so nobody can flood the board.
        $last = (int) Session::get('comment_at', 0);
        $wait = Comment::COOLDOWN - (time() - $last);
        if ($last > 0 && $wait > 0) {
            return Response::json(['ok' => false, 'reason' => 'too_fast',
                'message' => "Wait {$wait} second(s) before commenting again."], 429);
        }

        $name = trim((string) $viewer['first_name'] . ' ' . (string) $viewer['last_name']);
        Comment::add((string) $viewer['reference'], $name, $body);
        StreamEvent::log('comment', $name . ': ' . mb_substr($body, 0, 120));
        Session::put('comment_at', time());

        return Response::json([
            'ok' => true,
            'comments' => self::present(Comment::recent(100, (int) $request->str('after'))),
        ]);
    }

    /** Answer the organisers' poll or question. One answer per registration. */
    public function answerPrompt(Request $request): Response
    {
        $viewer = $this->passHolder();
        if ($viewer === null) {
            return Response::json(['ok' => false, 'reason' => 'signed_out'], 403);
        }
        $prompt = Prompt::find((int) $request->str('prompt_id'));
        if ($prompt === null || $prompt['status'] !== 'open') {
            return Response::json(['ok' => false, 'reason' => 'closed', 'message' => 'That one has closed.'], 410);
        }

        $author = trim((string) $viewer['first_name'] . ' ' . (string) $viewer['last_name']);
        if ($prompt['kind'] === 'poll') {
            $choice = (int) $request->str('choice', '-1');
            if (!isset($prompt['options'][$choice])) {
                return Response::json(['ok' => false, 'reason' => 'bad_choice', 'message' => 'Pick one of the options.'], 422);
            }
            Prompt::answer($prompt['id'], (string) $viewer['reference'], $author, $choice, null);
        } else {
            $text = trim($request->str('text'));
            if ($text === '') {
                return Response::json(['ok' => false, 'reason' => 'empty', 'message' => 'Write something first.'], 422);
            }
            Prompt::answer($prompt['id'], (string) $viewer['reference'], $author, null, $text);
        }

        return Response::json(['ok' => true, 'prompt' => self::promptFor($viewer)]);
    }

    /** The active prompt as this viewer should see it: options, whether they answered, poll results if so. */
    private static function promptFor(array $viewer): ?array
    {
        $prompt = Prompt::active();
        if ($prompt === null) {
            return null;
        }
        $mine = Prompt::hasAnswered($prompt['id'], (string) $viewer['reference']);
        $out = [
            'id' => $prompt['id'],
            'kind' => $prompt['kind'],
            'question' => e((string) $prompt['question']),
            'options' => array_map(static fn ($o) => e((string) $o), $prompt['options']),
            'answered' => $mine !== null,
            'my_choice' => $mine['choice'] ?? null,
        ];
        // Poll results are shown once they have voted; a question's replies stay with the organisers.
        if ($prompt['kind'] === 'poll' && $mine !== null) {
            $out['results'] = Prompt::tally($prompt);
        }

        return $out;
    }

    /** The viewer only if they still hold the single pass for their registration. */
    private function passHolder(): ?array
    {
        $viewer = $this->currentViewer();
        if ($viewer === null) {
            return null;
        }

        return $this->holds($viewer) ? $viewer : null;
    }

    /** Comments as the browser needs them. Escaping happens here, not in JS. */
    private static function present(array $rows): array
    {
        return array_map(static fn (array $row) => [
            'id'     => (int) $row['id'],
            'author' => e((string) $row['author_name']),
            'body'   => e((string) $row['body']),
            'at'     => date('H:i', strtotime((string) $row['created_at'])),
        ], $rows);
    }

    /**
     * Pass an HLS stream through the site, so the origin address is never
     * given to the browser. Only files under the configured stream can be
     * reached, and every nested playlist is rewritten to come back here.
     */
    public function hls(Request $request): Response
    {
        $viewer = $this->currentViewer();
        if ($viewer === null || !$this->holds($viewer)) {
            return Response::html('', 403);
        }
        if (!StreamService::isLive() || StreamService::kind() !== 'hls') {
            return Response::html('', 404);
        }

        $origin = StreamService::url();
        $root = rtrim((string) dirname($origin), '/');
        $file = $request->str('file') ?: basename((string) parse_url($origin, PHP_URL_PATH));

        $relative = self::safePath($file);
        if ($relative === null) {
            return Response::html('', 400);
        }

        [$status, $body, $type] = self::fetch($root . '/' . $relative);
        if ($status < 200 || $status >= 300 || $body === null) {
            return Response::html('', 502);
        }

        // A playlist points at more files; send those back through here too.
        if (str_contains($type, 'mpegurl') || str_ends_with($relative, '.m3u8')) {
            $dir = trim((string) dirname($relative), '.\/');
            $body = preg_replace_callback(
                '/^(?!#)(\S+)$/m',
                static function (array $m) use ($dir): string {
                    $resolved = self::resolve($dir, $m[1]);

                    return $resolved === null ? $m[1] : url('/watch/hls?file=' . rawurlencode($resolved));
                },
                $body
            ) ?? $body;

            // Encryption keys and maps are referenced inside comments.
            $body = preg_replace_callback(
                '/URI="([^"]+)"/',
                static function (array $m) use ($dir): string {
                    $resolved = self::resolve($dir, $m[1]);

                    return $resolved === null ? $m[0] : 'URI="' . url('/watch/hls?file=' . rawurlencode($resolved)) . '"';
                },
                $body
            ) ?? $body;

            $type = 'application/vnd.apple.mpegurl';
        }

        return (new Response($body, 200))
            ->header('Content-Type', $type !== '' ? $type : 'application/octet-stream')
            ->header('Cache-Control', 'no-store, private');
    }

    /** A relative path with no way out of the stream folder. */
    private static function safePath(string $path): ?string
    {
        $path = ltrim(trim($path), '/');
        if ($path === '' || strlen($path) > 300 || !preg_match('#^[A-Za-z0-9._/-]+$#', $path)) {
            return null;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $path;
    }

    /** Work out where a reference inside a playlist actually points. */
    private static function resolve(string $dir, string $reference): ?string
    {
        $reference = trim($reference);
        if ($reference === '' || str_starts_with($reference, '#')) {
            return null;
        }
        // An address somewhere else entirely is left alone.
        if (preg_match('#^https?://#i', $reference)) {
            return null;
        }

        $combined = $dir === '' ? ltrim($reference, '/') : $dir . '/' . ltrim($reference, '/');

        // Flatten any ../ the playlist used.
        $parts = [];
        foreach (explode('/', $combined) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $segment;
        }

        return self::safePath(implode('/', $parts));
    }

    /** @return array{0:int,1:?string,2:string} */
    private static function fetch(string $url): array
    {
        if (!SafeUrl::isPublicHttp($url)) {
            error_log('Stream fetch refused, not a public address: ' . $url);

            return [0, null, ''];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, SafeUrl::curlGuards() + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => ['Accept: */*'],
            // A stream segment is a few megabytes; anything larger is not ours.
            CURLOPT_BUFFERSIZE     => 65536,
            CURLOPT_NOPROGRESS     => false,
            CURLOPT_PROGRESSFUNCTION => static fn ($ch, $expected, $downloaded) => $downloaded > self::MAX_FETCH_BYTES ? 1 : 0,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        return [$status, $body === false ? null : (string) $body, $type];
    }

    /** An organiser signed in to the admin. They watch without a pass, and never take anyone's. */
    private function organiser(): ?array
    {
        $email = Session::get('admin_email');
        if (Session::get('admin_authenticated') !== true || !is_string($email) || $email === '') {
            return null;
        }

        return [
            'reference'          => 'ORGANISER',
            'first_name'         => 'Organiser',
            'last_name'          => '',
            'email'              => $email,
            'kingschat_username' => null,
            'participation'      => 'onsite',
            'status'             => 'confirmed',
            'payment_status'     => 'not_required',
            'is_organiser'       => true,
        ];
    }

    /** True for a pass held by this device, or for an organiser. */
    private function holds(array $viewer): bool
    {
        return !empty($viewer['is_organiser'])
            || StreamService::holdsPass((string) $viewer['reference'], VisitorTracker::sessionHash());
    }

    private function currentViewer(): ?array
    {
        $organiser = $this->organiser();
        if ($organiser !== null) {
            return $organiser;
        }

        $reference = Session::get(self::SESSION_KEY);
        if (!is_string($reference) || $reference === '') {
            return null;
        }
        $registration = \App\Models\Registration::findByReference($reference);

        return $registration !== null && StreamService::mayWatch($registration) ? $registration : null;
    }

    private function gate(Request $request, array $errors = []): Response
    {
        // Handed straight to the view: a flash would only appear on the next request.
        return $this->view('watch/gate', [
            'title'     => 'Watch — ' . config('app.name'),
            'bodyClass' => 'page-watch page-watch-gate',
            'noIndex'   => true,
            'live'      => StreamService::isLive(),
            'note'      => StreamService::note(),
            'holding'   => StreamService::holding(),
            'errors'    => $errors,
            'summit'    => config('app.summit'),
        ]);
    }
}

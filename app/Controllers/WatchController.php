<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Analytics;
use App\Services\StreamService;
use App\Services\VisitorTracker;

/** The protected stream page: the gate, the player, and the one-viewer rule. */
final class WatchController extends Controller
{
    private const SESSION_KEY = 'watch_reference';

    public function show(Request $request): Response
    {
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

        return $this->view('watch/player', [
            'title'     => StreamService::title(),
            'bodyClass' => 'page-watch',
            'presenceContext' => 'watch',
            'viewer'    => $viewer,
            'live'      => StreamService::isLive(),
            'kind'      => StreamService::kind(),
            'note'      => StreamService::note(),
            'summit'    => config('app.summit'),
        ]);
    }

    /** Check what was typed at the gate and hand over the single pass. */
    public function enter(Request $request): Response
    {
        $reference = $request->str('reference');
        $identifier = $request->str('identifier');

        if ($reference === '' || $identifier === '') {
            return $this->gate($request, ['auth' => 'Enter your reference and the email or KingsChat username you registered with.']);
        }

        $viewer = StreamService::findViewer($reference, $identifier);
        if ($viewer === null) {
            usleep(random_int(200_000, 500_000)); // Slow down guessing.
            return $this->gate($request, ['auth' => 'Those details do not match a confirmed registration for this summit.'], $reference);
        }

        $session = VisitorTracker::sessionHash();
        $claim = StreamService::claimPass(
            (string) $viewer['reference'],
            $session,
            Analytics::device($request->userAgent()),
            substr(hash_hmac('sha256', $request->ip(), (string) config('app.key')), 0, 32)
        );

        Session::put(self::SESSION_KEY, (string) $viewer['reference']);
        if ($claim['takenOver']) {
            Session::flash('watch_notice', 'You were watching on another device. That one has been signed out.');
        }

        return $this->redirect('/watch');
    }

    public function leave(Request $request): Response
    {
        $reference = Session::get(self::SESSION_KEY);
        if (is_string($reference)) {
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
        if (!StreamService::holdsPass((string) $viewer['reference'], VisitorTracker::sessionHash())) {
            return Response::json(['ok' => false, 'reason' => 'taken_over'], 409);
        }
        if (!StreamService::isLive()) {
            return Response::json(['ok' => true, 'live' => false]);
        }

        $kind = StreamService::kind();
        $source = $kind === 'hls' && StreamService::proxyEnabled()
            ? url('/watch/hls?file=' . rawurlencode(basename((string) parse_url(StreamService::url(), PHP_URL_PATH))))
            : ($kind === 'iframe' ? StreamService::embedUrl() : StreamService::url());

        return Response::json(['ok' => true, 'live' => true, 'kind' => $kind, 'source' => $source]);
    }

    /** Heartbeat: keeps the pass alive and reports when it has been taken. */
    public function beat(Request $request): Response
    {
        $viewer = $this->currentViewer();
        if ($viewer === null) {
            return Response::json(['ok' => false, 'reason' => 'signed_out'], 403);
        }

        $session = VisitorTracker::sessionHash();
        if (!StreamService::holdsPass((string) $viewer['reference'], $session)) {
            return Response::json(['ok' => false, 'reason' => 'taken_over'], 409);
        }

        StreamService::touchPass((string) $viewer['reference'], $session);
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

    /**
     * Pass an HLS stream through the site, so the origin address is never
     * given to the browser. Only files under the configured stream can be
     * reached, and every nested playlist is rewritten to come back here.
     */
    public function hls(Request $request): Response
    {
        $viewer = $this->currentViewer();
        if ($viewer === null || !StreamService::holdsPass((string) $viewer['reference'], VisitorTracker::sessionHash())) {
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
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_HTTPHEADER     => ['Accept: */*'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        return [$status, $body === false ? null : (string) $body, $type];
    }

    private function currentViewer(): ?array
    {
        $reference = Session::get(self::SESSION_KEY);
        if (!is_string($reference) || $reference === '') {
            return null;
        }
        $registration = \App\Models\Registration::findByReference($reference);

        return $registration !== null && StreamService::mayWatch($registration) ? $registration : null;
    }

    private function gate(Request $request, array $errors = [], string $reference = ''): Response
    {
        // Handed straight to the view: a flash would only appear on the next request.
        return $this->view('watch/gate', [
            'title'     => 'Watch — ' . config('app.name'),
            'bodyClass' => 'page-watch page-watch-gate',
            'live'      => StreamService::isLive(),
            'note'      => StreamService::note(),
            'errors'    => $errors,
            'reference' => $reference !== '' ? $reference : $request->str('reference'),
            'summit'    => config('app.summit'),
        ]);
    }
}

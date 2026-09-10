<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Session;
use App\Models\Analytics;

/**
 * Records a page view and keeps the visitor's presence fresh.
 *
 * Everything is first-party and server-side, so it needs no cookie banner
 * consent, cannot be blocked by an ad blocker, and never leaves the server.
 */
final class VisitorTracker
{
    /** Paths that say nothing about how the site is used. */
    private const IGNORED_PREFIXES = ['/admin', '/access/qr', '/api/'];

    public static function record(Request $request): void
    {
        if ($request->method !== 'GET' || self::isIgnored($request->path)) {
            return;
        }

        try {
            $device = Analytics::device($request->userAgent());
            if ($device === 'bot') {
                return;
            }

            $visitor = Analytics::visitorHash($request->ip(), $request->userAgent());
            $session = self::sessionHash();

            Analytics::record($request->path, $visitor, $session, $device, self::referrer());
            Analytics::touch($session, $visitor, $request->path, $device);

            // Cheap housekeeping, rarely, so the tables cannot grow forever.
            if (random_int(1, 500) === 1) {
                Analytics::prune();
            }
        } catch (\Throwable $e) {
            // Analytics must never break a page.
            error_log('Visit could not be recorded: ' . $e->getMessage());
        }
    }

    /** A stable id for this browsing session, independent of the login session. */
    public static function sessionHash(): string
    {
        $existing = Session::get('_visit');
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $hash = bin2hex(random_bytes(16));
        Session::put('_visit', $hash);

        return $hash;
    }

    private static function isIgnored(string $path): bool
    {
        foreach (self::IGNORED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Only where they came from, never the full address they arrived at. */
    private static function referrer(): ?string
    {
        $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referrer === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        // Movement inside the site is not a referral.
        $ourHost = parse_url(site_url(), PHP_URL_HOST);
        if (is_string($ourHost) && strcasecmp($host, $ourHost) === 0) {
            return null;
        }

        return strtolower(preg_replace('/^www\./', '', $host) ?? $host);
    }
}

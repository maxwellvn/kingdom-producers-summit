<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Analytics;
use App\Services\VisitorTracker;

/** The heartbeat that keeps "connected now" honest. */
final class PresenceController extends Controller
{
    public function beat(Request $request): Response
    {
        // A heartbeat belongs to a browsing session that already exists. Without
        // that, anyone could post repeatedly and invent an audience.
        if (!Session::has('_visit') || Session::get('_no_analytics') === true) {
            return Response::json(['ok' => true, 'counted' => false]);
        }

        $device = Analytics::device($request->userAgent());
        if ($device === 'bot') {
            return Response::json(['ok' => true, 'counted' => false]);
        }

        $context = $request->str('context') === 'watch' ? 'watch' : 'site';
        $path = mb_substr($request->str('path') ?: '/', 0, 190);

        Analytics::touch(
            VisitorTracker::sessionHash(),
            Analytics::visitorHash($request->ip(), $request->userAgent()),
            $path,
            $device,
            $context,
            null
        );

        return Response::json(['ok' => true, 'counted' => true]);
    }

    /** Remember, server-side, that this visitor declined measurement. */
    public function choice(Request $request): Response
    {
        $allowed = $request->str('analytics') !== '0';
        Session::put('_no_analytics', !$allowed);

        if (!$allowed) {
            Analytics::leave(VisitorTracker::sessionHash());
        }

        return Response::json(['ok' => true, 'counted' => $allowed]);
    }

    public function leave(Request $request): Response
    {
        Analytics::leave(VisitorTracker::sessionHash());

        return Response::json(['ok' => true]);
    }
}

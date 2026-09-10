<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Analytics;
use App\Services\VisitorTracker;

/** The heartbeat that keeps "connected now" honest. */
final class PresenceController extends Controller
{
    public function beat(Request $request): Response
    {
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

    public function leave(Request $request): Response
    {
        Analytics::leave(VisitorTracker::sessionHash());

        return Response::json(['ok' => true]);
    }
}

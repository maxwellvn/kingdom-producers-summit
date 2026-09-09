<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

final class VerifyCsrf
{
    public function handle(Request $request): ?Response
    {
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        if (Session::verifyCsrf($request->input('_token'))) {
            return null;
        }

        if ($request->wantsJson()) {
            return Response::json(['ok' => false, 'message' => 'Your session expired. Please refresh and try again.'], 403);
        }

        return Response::html(View::render('errors/403', ['title' => 'Session expired']), 403);
    }
}

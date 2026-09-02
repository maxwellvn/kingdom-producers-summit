<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

final class RequireAdmin
{
    public function handle(Request $request): ?Response
    {
        if (Session::get('admin_authenticated') === true) {
            return null;
        }

        Session::flash('_errors', ['auth' => 'Please sign in to continue.']);
        return Response::redirect(Url::to('/admin/login'));
    }
}

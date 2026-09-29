<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
require BASE_PATH . '/app/routes.php';

try {
    $request = Request::capture();
    // An old unprefixed link to an event page (/register, /watch) moves to Manchester's own address,
    // so every link on that page stays inside the event. Posts are left alone: old open tabs still submit.
    $legacy = \App\Core\Events::legacyRedirect($request);
    $response = $legacy ?? $router->dispatch($request);
} catch (Throwable $e) {
    error_log(sprintf('[%s] %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

    $response = Response::html(
        View::render('errors/500', [
            'title'  => 'Something went wrong',
            'detail' => config('app.debug') ? $e->getMessage() . ' — ' . basename($e->getFile()) . ':' . $e->getLine() : '',
        ]),
        500
    );
}

$response->send();

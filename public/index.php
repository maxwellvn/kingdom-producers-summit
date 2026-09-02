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
    $response = $router->dispatch(Request::capture());
} catch (Throwable $e) {
    error_log(sprintf('[%s] %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

    $response = Response::html(
        View::render('errors/500', [
            'title' => 'Something went wrong',
        ]),
        500
    );
}

$response->send();

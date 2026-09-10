<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string, 2: string[]}>> */
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path] = [$handler[0], $handler[1], $middleware];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $match = $this->routes[$method][$request->path] ?? null;

        if ($match === null) {
            $allowed = array_keys(array_filter(
                $this->routes,
                fn (array $paths) => isset($paths[$request->path])
            ));
            $status = $allowed ? 405 : 404;
            return Response::html(View::render('errors/' . $status, ['title' => (string) $status]), $status);
        }

        // Record the visit before the page is built, so a slow page still counts.
        \App\Services\VisitorTracker::record($request);

        [$class, $action, $middleware] = $match;

        foreach ($middleware as $mw) {
            $result = (new $mw())->handle($request);
            if ($result instanceof Response) {
                return $result;
            }
        }

        $controller = new $class();
        $response = $controller->{$action}($request);

        return $response instanceof Response ? $response : Response::html((string) $response);
    }
}

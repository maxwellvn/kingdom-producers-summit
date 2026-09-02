<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): Response
    {
        return Response::html(View::render($view, $data, $layout));
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(Url::to($path));
    }

    protected function back(Request $request, array $errors = [], array $old = []): Response
    {
        if ($errors) {
            Session::flash('_errors', $errors);
        }
        if ($old) {
            unset($old['_token'], $old['password']);
            Session::flash('_old', $old);
        }
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? Url::to('/'));
        return Response::redirect($referer);
    }
}

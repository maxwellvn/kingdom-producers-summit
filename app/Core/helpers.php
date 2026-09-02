<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Session;
use App\Core\Url;

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return (string) $value;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return Url::to($path);
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return Url::to('assets/' . ltrim($path, '/')) . $version;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Session::csrfToken()) . '">';
}

function old(string $key, mixed $default = ''): string
{
    $old = Session::get('_old', []);
    return e((string) ($old[$key] ?? $default));
}

function old_checked(string $key, string $value): string
{
    $old = Session::get('_old', []);
    $current = $old[$key] ?? null;
    if (is_array($current)) {
        return in_array($value, $current, true) ? 'checked' : '';
    }
    return (string) $current === $value ? 'checked' : '';
}

function error_for(string $key): ?string
{
    $errors = Session::get('_errors', []);
    return $errors[$key] ?? null;
}

function icon_arrow(string $class = 'icon-arrow'): string
{
    return sprintf(
        '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12l14 0"/><path d="M13 18l6 -6"/><path d="M13 6l6 6"/></svg>',
        e($class)
    );
}

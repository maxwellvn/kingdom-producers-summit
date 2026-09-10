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

/** An amount expressed in the event's Espees currency. Defaults to the onsite price. */
function espees_price(?int $amountPence = null, int $decimals = 0): string
{
    $amountPence ??= price_pence('onsite');
    return number_format($amountPence / 100, $decimals) . ' Espees';
}

/** What a participation path costs after the inaugural discount, in pence. */
function price_pence(string $participation): int
{
    return (int) (config('paypal.prices')[$participation]['due'] ?? 0);
}

/** A path's full price before the inaugural discount, in pence. */
function standard_price_pence(string $participation): int
{
    return (int) (config('paypal.prices')[$participation]['standard'] ?? 0);
}

/** True when a path has something to pay, so it needs the payment flow. */
function is_paid_path(string $participation): bool
{
    return price_pence($participation) > 0;
}

/** A registration's field, with the typed answer when they chose "Other". */
function field_label(array $registration): string
{
    $field = (string) ($registration['field'] ?? '');
    $other = trim((string) ($registration['field_other'] ?? ''));

    return $field === 'Other' && $other !== '' ? $other . ' (other)' : $field;
}

/**
 * Absolute site address for links and images in email.
 * Falls back to the current request when APP_URL is unset, so a missing
 * setting cannot silently break confirmation links.
 */
function site_url(): string
{
    $configured = rtrim((string) config('app.url'), '/');
    if ($configured !== '') {
        return $configured;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    // Host comes from the request, so accept only a plain host[:port].
    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)) {
        return '';
    }

    return ($https ? 'https://' : 'http://') . $host . \App\Core\Url::base();
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

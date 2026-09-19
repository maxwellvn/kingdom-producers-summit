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

/**
 * Large media (video) lives outside git. A local copy under public/assets/media wins
 * (development); otherwise MEDIA_URL, or failing that the pinned copy on jsDelivr.
 */
function media(string $file): string
{
    $file = ltrim($file, '/');
    if (is_file(BASE_PATH . '/public/assets/media/' . $file)) {
        return asset('media/' . $file);
    }
    $base = rtrim((string) env('MEDIA_URL', 'https://cdn.jsdelivr.net/gh/maxwellvn/kingdom-producers-summit@35c9dd5/public/assets/media'), '/');
    return $base . '/' . $file;
}

/** An amount expressed in the event's Espees currency. Defaults to the onsite price. */
function espees_price(?int $amountPence = null, int $decimals = 0): string
{
    $amountPence ??= price_pence('onsite');
    return number_format($amountPence / 100, $decimals) . ' Espees';
}

/**
 * An amount in the currency a payment method actually takes: Espees for the
 * Espees wallet, pounds sterling for card and bank payments.
 */
function amount_for(string $method, int $amountPence): string
{
    return $method === 'espees'
        ? number_format($amountPence / 100, 0) . ' Espees'
        : '£' . number_format($amountPence / 100, 2);
}

/**
 * What a registrant contributed, or the suggested amount if nothing yet, in
 * the currency of their chosen method. Before a method is chosen it is Espees.
 */
function paid_amount(array $registration): string
{
    $pence = (int) ($registration['payment_amount'] ?? 0) ?: price_pence((string) ($registration['participation'] ?? ''));

    return amount_for((string) ($registration['payment_method'] ?? 'espees'), $pence);
}

/**
 * The amount and how it was paid, as one phrase: "50 Espees", "£50.00 by card",
 * "£20.00 via Revolut". Espees is a currency in itself, so it is not repeated.
 */
function payment_phrase(array $registration): string
{
    $method = (string) ($registration['payment_method'] ?? '');
    $amount = paid_amount($registration);

    return match ($method) {
        'stripe'  => $amount . ' by card',
        'revolut' => $amount . ' via Revolut',
        default   => $amount,
    };
}

/**
 * A small mark for a payment method: the Espees coin, a generic card, or the
 * Revolut "R". Decorative, so hidden from screen readers.
 */
function payment_icon(string $method, int $size = 28): string
{
    $px = (string) $size;
    return match ($method) {
        'espees' => '<img class="pay-icon pay-icon--espees" src="' . e(asset('img/espees.png')) . '" width="' . $px . '" height="' . $px . '" alt="" aria-hidden="true">',
        'stripe' => '<svg class="pay-icon pay-icon--card" width="' . $px . '" height="' . $px . '" viewBox="0 0 28 28" fill="none" aria-hidden="true">'
            . '<rect x="2.5" y="6.5" width="23" height="15" rx="2.5" stroke="currentColor" stroke-width="1.6"/>'
            . '<rect x="2.5" y="10" width="23" height="3.2" fill="currentColor"/>'
            . '<rect x="6" y="16" width="7" height="2" rx="1" fill="currentColor"/>'
            . '</svg>',
        'revolut' => '<svg class="pay-icon pay-icon--revolut" width="' . $px . '" height="' . $px . '" viewBox="0 0 28 28" aria-hidden="true">'
            . '<rect x="2" y="2" width="24" height="24" rx="6" fill="currentColor"/>'
            . '<path d="M9.5 20.5V7.5h5.6c2.9 0 4.7 1.6 4.7 4.1 0 1.9-1.1 3.2-2.8 3.7l3.3 5.2h-3l-3-4.8h-2.1v4.8H9.5zm2.7-7.1h2.6c1.4 0 2.2-.7 2.2-1.8s-.8-1.8-2.2-1.8h-2.6v3.6z" fill="#F3EEE2"/>'
            . '</svg>',
        default => '',
    };
}

/** A readable name for a payment method. */
function payment_method_label(?string $method): string
{
    return ['espees' => 'Espees', 'revolut' => 'Revolut', 'stripe' => 'card'][(string) $method] ?? 'your chosen method';
}

/** The suggested contribution for a path, in pence. Zero for paths that are never asked. */
function price_pence(string $participation): int
{
    return (int) (config('pricing.prices')[$participation]['due'] ?? 0);
}

/** True when a path has a suggested contribution: onsite and online. Attending is free either way. */
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

/** The address people should write to. Never the retired lkps mailbox. */
function contact_email(): string
{
    $address = trim((string) config('app.mail.reply_to'));

    // A deployment still carrying the old mailbox would send people nowhere.
    if ($address === '' || str_starts_with(strtolower($address), 'lkps@')) {
        $address = trim((string) config('payments.proof.email'));
    }

    return $address !== '' ? $address : 'unitedkingdom@loveworldconsulate.org';
}

/** The KingsChat handle people can message, without the @. */
function contact_kingschat(): string
{
    return ltrim(trim((string) config('payments.proof.kingschat')), '@');
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

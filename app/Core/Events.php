<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Two events share one site. The first URL segment picks the event (/manchester/register,
 * /ireland/watch); it is stripped before routing, so every route is written once.
 * An unprefixed path is Manchester, so links already sent by email keep working.
 */
final class Events
{
    public const DEFAULT = 'manchester';
    public const SLUGS = ['manchester', 'ireland'];

    /** First path segments that belong to an event; admin, api, assets and webhooks do not. */
    private const SCOPED = ['register', 'watch', 'pass', 'sponsor', 'commitment', 'about', 'privacy', 'share', 'status'];

    private static string $active = self::DEFAULT;
    private static bool $prefixed = false;

    /** Read the event off the front of the path; returns the path the router should match. */
    public static function detect(string $path): string
    {
        self::$active = self::DEFAULT;
        self::$prefixed = false;

        if (preg_match('#^/(' . implode('|', self::SLUGS) . ')(/.*)?$#', $path, $m)) {
            self::$active = $m[1];
            self::$prefixed = true;
            return $m[2] ?? '/';
        }

        // Admin works on one event at a time, picked with the switch in the admin header.
        if (str_starts_with($path, '/admin')) {
            $chosen = Session::get('admin_event');
            self::$active = in_array($chosen, self::SLUGS, true) ? $chosen : self::DEFAULT;
        }

        return $path;
    }

    public static function active(): string
    {
        return self::$active;
    }

    /** "Manchester" / "Ireland", for admin headings and messages. */
    public static function label(?string $slug = null): string
    {
        return ucfirst($slug ?? self::$active);
    }

    public static function isValid(string $slug): bool
    {
        return in_array($slug, self::SLUGS, true);
    }

    /** True on "/", the page that asks which event; false on an event's own home. */
    public static function isChooser(string $path): bool
    {
        return $path === '/' && !self::$prefixed;
    }

    /** Put the active event in front of a path that belongs to an event. */
    public static function scope(string $path): string
    {
        $first = preg_split('#[/?\#]#', ltrim($path, '/'))[0];
        // Home, seen from outside an event (the chooser, or an old unprefixed link), is the chooser.
        if ($first === '' && !self::$prefixed) {
            return $path;
        }
        if ($first !== '' && !in_array($first, self::SCOPED, true)) {
            return $path;
        }

        return '/' . self::$active . '/' . ltrim($path, '/');
    }

    /** A GET for an event page with no event in its path: send it to the default event's copy. */
    public static function legacyRedirect(Request $request): ?Response
    {
        if (self::$prefixed || $request->method !== 'GET' || $request->path === '/') {
            return null;
        }
        $scoped = self::scope($request->path);
        if ($scoped === $request->path) {
            return null;
        }
        $query = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);

        return Response::redirect(Url::base() . $scoped . ($query !== '' ? '?' . $query : ''), 301);
    }

    /** The settings key holding this event's date and venue. Manchester keeps the original key. */
    public static function settingKey(): string
    {
        return self::$active === self::DEFAULT ? 'summit_edition' : 'summit_edition_' . self::$active;
    }

    /** config('app.summit') with the active event's own block laid over it. */
    public static function baseSummit(array $app): array
    {
        $own = (array) ($app['events'][self::$active] ?? []);

        return array_replace_recursive((array) $app['summit'], $own);
    }

    /** Run a callback as another event, for pages that show both (the chooser). */
    public static function using(string $slug, callable $fn): mixed
    {
        $before = self::$active;
        self::$active = in_array($slug, self::SLUGS, true) ? $slug : self::DEFAULT;
        try {
            return $fn();
        } finally {
            self::$active = $before;
        }
    }

    /** One event's full summit block: config, its own block, then what admin saved. */
    public static function summit(string $slug): array
    {
        return self::using($slug, static fn (): array => (array) Config::get('app.summit'));
    }
}

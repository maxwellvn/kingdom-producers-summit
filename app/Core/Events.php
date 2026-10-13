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

    /** Setting holding the events taken off the public site, as a JSON list of slugs. Site-wide, not per event. */
    private const HIDDEN_SETTING = 'events_hidden';

    /** @return array<int,string> slugs switched off in admin */
    public static function hidden(): array
    {
        try {
            $list = json_decode(\App\Models\Setting::get(self::HIDDEN_SETTING, '[]'), true);
        } catch (\Throwable) {
            return []; // no database yet: everything shows
        }

        return array_values(array_intersect(self::SLUGS, is_array($list) ? $list : []));
    }

    public static function isPublic(string $slug): bool
    {
        return !in_array($slug, self::hidden(), true);
    }

    /** @return array<int,string> the events visitors can see; never empty */
    public static function publicSlugs(): array
    {
        return array_values(array_diff(self::SLUGS, self::hidden())) ?: [self::DEFAULT];
    }

    /** Show or hide one event. Refuses to hide the last visible one. */
    public static function setHidden(string $slug, bool $hide): bool
    {
        if (!self::isValid($slug)) {
            return false;
        }
        $hidden = self::hidden();
        $hidden = $hide ? array_values(array_unique([...$hidden, $slug])) : array_values(array_diff($hidden, [$slug]));
        if (count($hidden) >= count(self::SLUGS)) {
            return false;
        }
        \App\Models\Setting::set(self::HIDDEN_SETTING, (string) json_encode($hidden));

        return true;
    }

    /**
     * Visitors to a hidden event's pages go to the front door instead; signed-in admins can still open them
     * (with a preview bar), so an event can be prepared before it is shown. With one event left, the front
     * door is that event, for admins too.
     */
    public static function visibilityRedirect(Request $request): ?Response
    {
        if (str_starts_with($request->path, '/admin') || str_starts_with($request->path, '/api') || str_starts_with($request->path, '/webhooks')) {
            return null;
        }
        // Admins may open a hidden event's own pages to prepare them; the front door behaves as for everyone.
        if (self::$prefixed && !self::isPublic(self::$active) && Session::get('admin_authenticated') !== true) {
            return Response::redirect(Url::base() . '/', 302);
        }
        $public = self::publicSlugs();
        if (!self::$prefixed && $request->path === '/' && count($public) === 1) {
            return Response::redirect(Url::base() . '/' . $public[0] . '/', 302);
        }

        return null;
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

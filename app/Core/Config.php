<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $items = [];

    /** @var array<string,array> each event's summit block, built once per request when first asked for */
    private static array $laid = [];

    /** app.php as written, before any event or admin edit is laid over it. */
    private static array $rawApp = [];

    public static function load(string $dir): void
    {
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            self::$items[basename($file, '.php')] = require $file;
        }
        self::$rawApp = (array) (self::$items['app'] ?? []);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // The active event's edition (its config block, then what admin saved) sits over the file.
        // Kept per event, so work done for another event (sending its announcements) reads its own.
        if (($key === 'app' || str_starts_with($key, 'app.summit')) && isset(self::$rawApp['summit'])) {
            $event = Events::active();
            self::$laid[$event] ??= \App\Services\Edition::overlay(Events::baseSummit(self::$rawApp));
            self::$items['app']['summit'] = self::$laid[$event];
        }

        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

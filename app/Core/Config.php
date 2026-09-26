<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $items = [];

    private static bool $editionLaid = false;

    public static function load(string $dir): void
    {
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            self::$items[basename($file, '.php')] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // The edition set in the admin panel sits over the file, read once per request when first asked for.
        if (!self::$editionLaid && ($key === 'app' || str_starts_with($key, 'app.summit')) && isset(self::$items['app']['summit'])) {
            self::$editionLaid = true;
            self::$items['app']['summit'] = \App\Services\Edition::overlay(self::$items['app']['summit']);
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

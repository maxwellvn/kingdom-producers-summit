<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $items = [];

    public static function load(string $dir): void
    {
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            self::$items[basename($file, '.php')] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
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

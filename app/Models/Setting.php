<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Key/value settings maintained from the admin panel (payment methods and their details). */
final class Setting
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            $rows = Database::connection()->query('SELECT `key`, value FROM settings')->fetchAll();
            self::$cache = array_column($rows, 'value', 'key');
        }

        return isset(self::$cache[$key]) ? (string) self::$cache[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([$key, $value]);

        // Keep the rest of this request consistent with what was just written.
        self::$cache = null;
    }
}

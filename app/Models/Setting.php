<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Admin-editable key/value settings (payment method toggles and display details). */
final class Setting
{
    /** @param array<string,string> $defaults */
    public static function get(string $key, string $default = ''): string
    {
        static $cache = null;
        if ($cache === null) {
            $rows = Database::connection()->query('SELECT `key`, value FROM settings')->fetchAll();
            $cache = array_column($rows, 'value', 'key');
        }
        return isset($cache[$key]) ? (string) $cache[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([$key, $value]);
    }
}

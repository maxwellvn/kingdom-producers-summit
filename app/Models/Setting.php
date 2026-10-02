<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Key/value settings maintained from the admin panel (payment methods and their details). */
final class Setting
{
    /** Keys each event keeps its own copy of; Ireland's are stored as key_ireland. Payment details stay shared. */
    private const PER_EVENT = [
        'stream_state', 'stream_enabled', 'stream_starts_at', 'stream_message', 'stream_headline', 'stream_now',
        'stream_url', 'stream_url_sd', 'stream_title', 'stream_proxy', 'stream_note',
        'watch_open_token', 'comments_enabled', 'live_link_mailed_at', 'express_registration',
    ];

    private static function scoped(string $key): string
    {
        $event = \App\Core\Events::active();

        return $event !== \App\Core\Events::DEFAULT && in_array($key, self::PER_EVENT, true) ? $key . '_' . $event : $key;
    }

    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            $rows = Database::connection()->query('SELECT `key`, value FROM settings')->fetchAll();
            self::$cache = array_column($rows, 'value', 'key');
        }

        $key = self::scoped($key);

        return isset(self::$cache[$key]) ? (string) self::$cache[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([self::scoped($key), $value]);

        // Keep the rest of this request consistent with what was just written.
        self::$cache = null;
    }
}

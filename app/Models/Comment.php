<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Events;

/**
 * Comments left by viewers during the event. They are only collected while an
 * organiser has them switched on, and an organiser can clear them at any time.
 */
final class Comment
{
    public const MAX_LENGTH = 500;

    /** Seconds a viewer must wait between comments. */
    public const COOLDOWN = 2;

    /** True only while an organiser has switched comments on. */
    public static function enabled(): bool
    {
        return Setting::get('comments_enabled', '0') === '1';
    }

    /**
     * The most recent comments, oldest first so they read top to bottom.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function recent(int $limit = 100, int $afterId = 0): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = Database::connection()->prepare(
            "SELECT id, author_name, body, created_at
             FROM comments
             WHERE event = :event AND id > :after
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['event' => Events::active(), 'after' => max(0, $afterId)]);

        return array_reverse($stmt->fetchAll());
    }

    public static function add(string $reference, string $authorName, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO comments (event, reference, author_name, body) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            Events::active(),
            $reference,
            mb_substr(trim($authorName), 0, 120),
            mb_substr(trim($body), 0, self::MAX_LENGTH),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM comments WHERE id = ? AND event = ?')->execute([$id, Events::active()]);
    }

    /** Remove every comment. Used when an organiser clears the board. */
    public static function clearAll(): int
    {
        $pdo = Database::connection();
        $count = self::count();
        $pdo->prepare('DELETE FROM comments WHERE event = ?')->execute([Events::active()]);

        return $count;
    }

    public static function count(): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM comments WHERE event = ?');
        $stmt->execute([Events::active()]);

        return (int) $stmt->fetchColumn();
    }

    /** Newest first, for the moderation table. */
    public static function forModeration(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = Database::connection()->prepare("SELECT id, reference, author_name, body, created_at FROM comments WHERE event = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([Events::active()]);

        return $stmt->fetchAll();
    }
}

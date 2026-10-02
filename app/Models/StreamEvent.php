<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Events;

/** A running log of what happens around the stream, shown live in the admin. */
final class StreamEvent
{
    /** kind: signin, signout, takeover, refused, comment, stream, comments, loadtest, proxy */
    public static function log(string $kind, string $detail): void
    {
        try {
            Database::connection()
                ->prepare('INSERT INTO stream_events (event, kind, detail) VALUES (?, ?, ?)')
                ->execute([Events::active(), mb_substr($kind, 0, 24), mb_substr($detail, 0, 255)]);
        } catch (\Throwable $e) {
            error_log('Stream event not logged: ' . $e->getMessage()); // never let logging break the page
        }
    }

    /** Events newer than an id, oldest first. With no id, the most recent page. */
    public static function since(int $afterId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $pdo = Database::connection();
        if ($afterId > 0) {
            $stmt = $pdo->prepare("SELECT id, at, kind, detail FROM stream_events WHERE event = ? AND id > ? ORDER BY id LIMIT {$limit}");
            $stmt->execute([Events::active(), $afterId]);
            return $stmt->fetchAll();
        }
        $stmt = $pdo->prepare("SELECT id, at, kind, detail FROM stream_events WHERE event = ? ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute([Events::active()]);
        $rows = $stmt->fetchAll();

        return array_reverse($rows);
    }

    public static function clear(): void
    {
        Database::connection()->prepare('DELETE FROM stream_events WHERE event = ?')->execute([Events::active()]);
    }

    /** Keep the table from growing forever: a week is plenty. */
    public static function prune(): void
    {
        Database::connection()->exec('DELETE FROM stream_events WHERE at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
    }
}

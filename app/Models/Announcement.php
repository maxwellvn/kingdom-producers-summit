<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Announcement
{
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO announcements (audience, subject, body, by_email, by_kingschat, send_at, created_by)
             VALUES (:audience, :subject, :body, :by_email, :by_kingschat, :send_at, :created_by)'
        );
        $stmt->execute($data);

        return (int) $pdo->lastInsertId();
    }

    /** @return array<int,array> */
    public static function recent(int $limit = 50): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM announcements ORDER BY (sent_at IS NULL) DESC, send_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Take one due announcement, marking it as taken in the same statement so two
     * runners cannot send it twice. Returns null when nothing is due.
     */
    public static function claimDue(): ?array
    {
        $pdo = Database::connection();
        $pdo->exec("UPDATE announcements SET sent_at = NOW(), result = 'sending'
                    WHERE sent_at IS NULL AND send_at <= NOW() ORDER BY send_at LIMIT 1");
        $stmt = $pdo->query("SELECT * FROM announcements WHERE result = 'sending' ORDER BY sent_at LIMIT 1");

        return $stmt->fetch() ?: null;
    }

    public static function finish(int $id, string $result): void
    {
        $stmt = Database::connection()->prepare('UPDATE announcements SET result = ? WHERE id = ?');
        $stmt->execute([$result, $id]);
    }

    /** Only something not yet sent can be cancelled. */
    public static function cancel(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM announcements WHERE id = ? AND sent_at IS NULL');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}

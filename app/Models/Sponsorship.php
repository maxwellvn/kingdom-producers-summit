<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Sponsorship
{
    public const MIN_PENCE = 500;
    public const MAX_PENCE = 1000000;

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO sponsorships (name, email, amount_pence, method)
             VALUES (:name, :email, :amount_pence, :method)'
        );
        $stmt->execute($data);

        return (int) $pdo->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM sponsorships WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function setSession(int $id, string $sessionId): void
    {
        $stmt = Database::connection()->prepare('UPDATE sponsorships SET session_id = ? WHERE id = ?');
        $stmt->execute([$sessionId, $id]);
    }

    /** Giver says the Espees or Revolut transfer has gone. An organiser confirms it later. */
    public static function claim(int $id): bool
    {
        $stmt = Database::connection()->prepare("UPDATE sponsorships SET status = 'claimed' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /** Idempotent: the webhook and the return page may both report the same payment. */
    public static function markPaid(int $id, string $sessionId, int $amountPence): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE sponsorships SET status = 'paid', session_id = ?, amount_pence = ?, paid_at = NOW()
             WHERE id = ? AND status <> 'paid'"
        );
        $stmt->execute([$sessionId, $amountPence, $id]);

        return $stmt->rowCount() > 0;
    }

    /** @return array<int, array> newest first */
    public static function recent(int $limit = 200): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM sponsorships ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function totalPaidPence(): int
    {
        return (int) Database::connection()->query("SELECT COALESCE(SUM(amount_pence), 0) FROM sponsorships WHERE status = 'paid'")->fetchColumn();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Events;

/** Applications to exhibit, one event at a time. */
final class Exhibitor
{
    public const CATEGORIES = [
        'Products', 'Food and drink', 'Fashion and beauty', 'Arts, crafts and design', 'Books and media',
        'Technology and digital', 'Services and consulting', 'Finance and property', 'Health and wellbeing',
        'Education and training', 'Charity or ministry', 'Other',
    ];
    public const STATUSES = ['pending', 'approved', 'declined'];

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO exhibitors (event, first_name, last_name, email, phone, kingschat, business, category, description, website, staff, needs_power, notes)
             VALUES (:event, :first_name, :last_name, :email, :phone, :kingschat, :business, :category, :description, :website, :staff, :needs_power, :notes)'
        );
        $stmt->execute(['event' => Events::active()] + $data);

        return (int) $pdo->lastInsertId();
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM exhibitors WHERE event = ? AND email = ? LIMIT 1');
        $stmt->execute([Events::active(), mb_strtolower(trim($email))]);

        return (bool) $stmt->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM exhibitors WHERE id = ? AND event = ? LIMIT 1');
        $stmt->execute([$id, Events::active()]);

        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array<string,mixed>> newest first; '' status means all */
    public static function all(string $status = ''): array
    {
        $sql = 'SELECT * FROM exhibitors WHERE event = ?';
        $params = [Events::active()];
        if (in_array($status, self::STATUSES, true)) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $stmt = Database::connection()->prepare($sql . ' ORDER BY created_at DESC, id DESC');
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array{pending:int,approved:int,declined:int} */
    public static function counts(): array
    {
        $stmt = Database::connection()->prepare('SELECT status, COUNT(*) FROM exhibitors WHERE event = ? GROUP BY status');
        $stmt->execute([Events::active()]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: []) + ['pending' => 0, 'approved' => 0, 'declined' => 0];
    }

    /** Only a real change counts, so a double click never sends the email twice. */
    public static function decide(int $id, string $status, string $by): bool
    {
        if (!in_array($status, ['approved', 'declined'], true)) {
            return false;
        }
        $stmt = Database::connection()->prepare(
            'UPDATE exhibitors SET status = ?, decided_by = ?, decided_at = NOW() WHERE id = ? AND event = ? AND status <> ?'
        );
        $stmt->execute([$status, $by, $id, Events::active(), $status]);

        return $stmt->rowCount() > 0;
    }
}

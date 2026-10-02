<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Events;

/** The four commitments made at the end of the session. */
final class Commitment
{
    public const ITEMS = [
        'produce' => 'I will produce something this year.',
        'records' => 'I will keep proper business records.',
        'buy'     => 'I will buy from a LoveWorld business within 30 days.',
        'teach'   => 'I will teach one person what I know.',
    ];

    public const TITLES = ['Mr', 'Mrs', 'Ms', 'Miss', 'Brother', 'Sister', 'Dr', 'Pastor', 'Deacon', 'Deaconess', 'Rev'];

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO commitments (event, title, first_name, last_name, email, kingschat, produce, records, buy, teach) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([Events::active(), $data['title'], $data['first_name'], $data['last_name'], $data['email'], $data['kingschat'], $data['produce'], $data['records'], $data['buy'], $data['teach']]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM commitments WHERE event = ? ORDER BY created_at DESC');
        $stmt->execute([Events::active()]);

        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM commitments WHERE event = ?');
        $stmt->execute([Events::active()]);

        return (int) $stmt->fetchColumn();
    }
}

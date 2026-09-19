<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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
            'INSERT INTO commitments (title, first_name, last_name, email, kingschat, produce, records, buy, teach) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$data['title'], $data['first_name'], $data['last_name'], $data['email'], $data['kingschat'], $data['produce'], $data['records'], $data['buy'], $data['teach']]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM commitments ORDER BY created_at DESC')->fetchAll();
    }

    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM commitments')->fetchColumn();
    }
}

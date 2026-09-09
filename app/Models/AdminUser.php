<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Additional admin accounts created from the panel; the env admin stays root. */
final class AdminUser
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM admins WHERE email = ? LIMIT 1'
        );
        $stmt->execute([mb_strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** @return list<array{id:int,email:string,created_at:string}> */
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, email, created_at FROM admins ORDER BY created_at ASC')
            ->fetchAll();
    }

    public static function create(string $email, string $passwordHash): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO admins (email, password_hash) VALUES (?, ?)'
        );
        $stmt->execute([mb_strtolower(trim($email)), $passwordHash]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM admins WHERE id = ?');
        $stmt->execute([$id]);
    }
}

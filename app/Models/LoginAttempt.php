<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Failed admin sign-ins, stored server-side so a discarded session cookie
 * cannot reset the lockout counter.
 */
final class LoginAttempt
{
    public static function countRecent(string $identifier, int $windowSeconds): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > (NOW() - INTERVAL ? SECOND)'
        );
        $stmt->execute([$identifier, $windowSeconds]);
        return (int) $stmt->fetchColumn();
    }

    public static function record(string $identifier): void
    {
        Database::connection()
            ->prepare('INSERT INTO login_attempts (identifier) VALUES (?)')
            ->execute([mb_substr($identifier, 0, 190)]);

        // ponytail: prune on write keeps the table small without a scheduled job.
        Database::connection()->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }

    public static function clear(string $identifier): void
    {
        Database::connection()
            ->prepare('DELETE FROM login_attempts WHERE identifier = ?')
            ->execute([$identifier]);
    }

    /** Seconds remaining before another attempt is allowed, or 0 when not locked. */
    public static function lockedForSeconds(string $identifier, int $maxAttempts, int $windowSeconds): int
    {
        if (self::countRecent($identifier, $windowSeconds) < $maxAttempts) {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), MAX(attempted_at) + INTERVAL ? SECOND) FROM login_attempts WHERE identifier = ?'
        );
        $stmt->execute([$windowSeconds, $identifier]);

        return max(1, (int) $stmt->fetchColumn());
    }
}

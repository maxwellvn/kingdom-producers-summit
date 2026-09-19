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
     * Send whatever is due, from ordinary page traffic, no more than once a
     * minute. A scheduled message then goes out within a minute of anyone
     * visiting, whether or not a background runner exists on the server.
     * The work happens after the response is sent, so the visitor is not kept waiting.
     */
    public static function sendDueIfAny(): void
    {
        $last = (int) Setting::get('announcements_swept_at', '0');
        if (time() - $last < 60) {
            return;
        }
        try {
            $due = (int) Database::connection()->query("SELECT COUNT(*) FROM announcements WHERE (sent_at IS NULL AND send_at <= NOW()) OR result = 'sending'")->fetchColumn();
        } catch (\Throwable $e) {
            return; // table not there yet
        }
        Setting::set('announcements_swept_at', (string) time());
        if ($due === 0) {
            return;
        }
        // Finish the page first, then send. FastCGI hands the response over; Apache mod_php just carries on.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        ignore_user_abort(true);
        set_time_limit(0);
        register_shutdown_function(static function (): void {
            require BASE_PATH . '/bin/send-due.php';
        });
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

    /** Claimed but not finished: the queue still has people waiting. */
    public static function inFlight(): array
    {
        return Database::connection()->query("SELECT * FROM announcements WHERE result = 'sending' ORDER BY sent_at")->fetchAll() ?: [];
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

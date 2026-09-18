<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** A poll or an open question put to viewers during the stream. */
final class Prompt
{
    public const MAX_OPTIONS = 6;
    public const MAX_TEXT = 500;

    /** @param string[] $options empty for a question */
    public static function create(string $kind, string $question, array $options): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO prompts (kind, question, options) VALUES (?, ?, ?)')
            ->execute([$kind, mb_substr(trim($question), 0, 255), $kind === 'poll' ? json_encode(array_values($options), JSON_UNESCAPED_UNICODE) : null]);

        return (int) $pdo->lastInsertId();
    }

    public static function close(int $id): void
    {
        Database::connection()->prepare("UPDATE prompts SET status = 'closed', closed_at = NOW() WHERE id = ? AND status = 'open'")->execute([$id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM prompts WHERE id = ?')->execute([$id]);
    }

    /** The one prompt viewers should see right now: the newest open one. */
    public static function active(): ?array
    {
        $row = Database::connection()->query("SELECT * FROM prompts WHERE status = 'open' ORDER BY id DESC LIMIT 1")->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM prompts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    /** Newest first, with answer counts, for the admin. */
    public static function all(int $limit = 50): array
    {
        $rows = Database::connection()->query(
            "SELECT p.*, (SELECT COUNT(*) FROM prompt_answers a WHERE a.prompt_id = p.id) AS answers
             FROM prompts p ORDER BY p.id DESC LIMIT " . max(1, min(200, $limit))
        )->fetchAll();

        return array_map([self::class, 'hydrate'], $rows);
    }

    /** One answer per registration. Returns false when they had already answered. */
    public static function answer(int $promptId, string $reference, string $author, ?int $choice, ?string $text): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO prompt_answers (prompt_id, reference, author, choice, text) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$promptId, $reference, mb_substr($author, 0, 120), $choice, $text === null ? null : mb_substr(trim($text), 0, self::MAX_TEXT)]);

        return $stmt->rowCount() > 0;
    }

    public static function hasAnswered(int $promptId, string $reference): ?array
    {
        $stmt = Database::connection()->prepare('SELECT choice, text FROM prompt_answers WHERE prompt_id = ? AND reference = ?');
        $stmt->execute([$promptId, $reference]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Poll tallies: [count per option index], plus the total. */
    public static function tally(array $prompt): array
    {
        $options = $prompt['options'] ?? [];
        $counts = array_fill(0, count($options), 0);
        $stmt = Database::connection()->prepare('SELECT choice, COUNT(*) AS n FROM prompt_answers WHERE prompt_id = ? AND choice IS NOT NULL GROUP BY choice');
        $stmt->execute([(int) $prompt['id']]);
        $total = 0;
        foreach ($stmt->fetchAll() as $row) {
            $i = (int) $row['choice'];
            if (isset($counts[$i])) {
                $counts[$i] = (int) $row['n'];
                $total += (int) $row['n'];
            }
        }

        return ['counts' => $counts, 'total' => $total];
    }

    /** Text replies to a question, newest first, for the admin. */
    public static function replies(int $promptId, int $limit = 200): array
    {
        $stmt = Database::connection()->prepare('SELECT author, reference, text, created_at FROM prompt_answers WHERE prompt_id = ? AND text IS NOT NULL ORDER BY id DESC LIMIT ' . max(1, min(500, $limit)));
        $stmt->execute([$promptId]);

        return $stmt->fetchAll();
    }

    private static function hydrate(array $row): array
    {
        $row['options'] = $row['options'] !== null ? (json_decode((string) $row['options'], true) ?: []) : [];
        $row['id'] = (int) $row['id'];

        return $row;
    }
}

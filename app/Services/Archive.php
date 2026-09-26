<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Past editions. Archiving copies every per-edition table to archive_<slug>_<table>,
 * checks the copies, and (when asked) empties the live tables so the next edition starts clean.
 * Initiative members and commitments are copied too but stay live: the Initiative is not tied to one summit.
 * Admins, settings, sessions and consent records are site-wide and stay put.
 */
final class Archive
{
    /** The Kingdom Producers Initiative runs across editions: its members and commitments stay live. */
    private const KEEP_LIVE = ['commitments'];

    /** Per-edition tables, children before parents so emptying never trips a foreign key. */
    public const TABLES = [
        'registrations'           => 'Registrations',
        'attendances'             => 'Check-ins',
        'watch_passes'            => 'Watch passes',
        'commitments'             => 'Commitments',
        'sponsorships'            => 'Sponsorships',
        'comments'                => 'Live chat',
        'prompt_answers'          => 'Poll answers',
        'prompts'                 => 'Polls',
        'announcement_deliveries' => 'Email deliveries',
        'announcements'           => 'Announcements',
        'stream_events'           => 'Stream log',
        'presence'                => 'Viewers',
        'page_views'              => 'Page views',
    ];

    /** Columns kept out of the browser and the exports, as the registrations export does. */
    private const HIDDEN = ['ip_address', 'user_agent'];

    public static function slugFor(string $label): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');

        return substr($slug, 0, 24);
    }

    /** @return array<int,array<string,mixed>> newest first */
    public static function all(): array
    {
        $rows = Database::connection()->query('SELECT * FROM archives ORDER BY created_at DESC, id DESC')->fetchAll() ?: [];

        return array_map(self::decode(...), $rows);
    }

    public static function find(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM archives WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ? self::decode($row) : null;
    }

    /**
     * Copy the live edition into an archive. With $clear the live tables are emptied afterwards,
     * but only once every copy has been counted and matches.
     * @return string|null an error to show, or null when it worked
     */
    public static function create(string $label, bool $clear, string $by): ?string
    {
        $label = trim($label);
        $slug = self::slugFor($label);
        if ($slug === '' || mb_strlen($label) > 120) {
            return 'Give the archive a name, such as "London 2026".';
        }
        if (self::find($slug) !== null) {
            return 'There is already an archive called "' . $label . '".';
        }

        $pdo = Database::connection();
        $counts = [];
        $made = [];
        try {
            foreach (array_keys(self::TABLES) as $table) {
                if (!self::exists($table)) {
                    continue;
                }
                $copy = self::table($slug, $table);
                // DDL commits on its own in MySQL, so safety comes from checking the copies, not a transaction.
                $pdo->exec("CREATE TABLE `{$copy}` LIKE `{$table}`");
                $made[] = $copy;
                $pdo->exec("INSERT INTO `{$copy}` SELECT * FROM `{$table}`");
                $live = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                $copied = (int) $pdo->query("SELECT COUNT(*) FROM `{$copy}`")->fetchColumn();
                if ($live !== $copied) {
                    throw new \RuntimeException("{$table}: {$copied} of {$live} rows copied");
                }
                $counts[$table] = $copied;
            }
        } catch (\Throwable $e) {
            foreach ($made as $copy) {
                $pdo->exec("DROP TABLE IF EXISTS `{$copy}`");
            }
            error_log('Archive failed: ' . $e->getMessage());

            return 'The archive could not be made, so nothing was changed: ' . $e->getMessage();
        }

        $pdo->prepare('INSERT INTO archives (slug, label, counts, cleared, created_by) VALUES (?, ?, ?, 0, ?)')
            ->execute([$slug, $label, json_encode($counts), $by]);

        return $clear ? self::clearLive($slug) : null;
    }

    /** Empty the live per-edition tables. Only ever called after the archive checked out. */
    private static function clearLive(string $slug): ?string
    {
        $pdo = Database::connection();
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach (array_keys(self::TABLES) as $table) {
                if (!self::exists($table) || in_array($table, self::KEEP_LIVE, true)) {
                    continue;
                }
                // Summit places go; Initiative members carry on into the next edition.
                $pdo->exec($table === 'registrations'
                    ? "DELETE FROM `registrations` WHERE participation <> 'initiative'"
                    : "TRUNCATE TABLE `{$table}`");
            }
        } catch (\Throwable $e) {
            error_log('Clearing live tables failed: ' . $e->getMessage());

            return 'The archive is safe, but emptying the live tables stopped part way: ' . $e->getMessage();
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
        $pdo->prepare('UPDATE archives SET cleared = 1 WHERE slug = ?')->execute([$slug]);

        return null;
    }

    public static function rename(string $slug, string $label): bool
    {
        $label = trim($label);
        if ($label === '' || mb_strlen($label) > 120) {
            return false;
        }
        $stmt = Database::connection()->prepare('UPDATE archives SET label = ? WHERE slug = ?');
        $stmt->execute([$label, $slug]);

        return $stmt->rowCount() > 0;
    }

    /** Drops the archive's tables for good. */
    public static function delete(string $slug): bool
    {
        if (self::find($slug) === null) {
            return false;
        }
        $pdo = Database::connection();
        foreach (array_keys(self::TABLES) as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . self::table($slug, $table) . '`');
        }
        $pdo->prepare('DELETE FROM archives WHERE slug = ?')->execute([$slug]);

        return true;
    }

    /** @return array{columns:array<int,string>,rows:array<int,array<string,mixed>>,total:int,page:int,pages:int} */
    public static function rows(string $slug, string $table, string $search = '', int $page = 1, int $perPage = 50): array
    {
        $copy = self::table($slug, $table);
        $columns = self::columns($copy);
        if ($columns === []) {
            return ['columns' => [], 'rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
        }
        [$where, $params] = self::where($copy, $search);
        $pdo = Database::connection();

        $count = $pdo->prepare("SELECT COUNT(*) FROM `{$copy}`{$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $list = implode(', ', array_map(static fn ($c) => "`{$c}`", $columns));
        $stmt = $pdo->prepare("SELECT {$list} FROM `{$copy}`{$where} ORDER BY 1 LIMIT " . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
        $stmt->execute($params);

        return ['columns' => $columns, 'rows' => $stmt->fetchAll() ?: [], 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** Every row of one archived table, for the CSV. */
    public static function each(string $slug, string $table, string $search = ''): \Generator
    {
        $copy = self::table($slug, $table);
        $columns = self::columns($copy);
        yield $columns;
        if ($columns === []) {
            return;
        }
        [$where, $params] = self::where($copy, $search);
        $list = implode(', ', array_map(static fn ($c) => "`{$c}`", $columns));
        $stmt = Database::connection()->prepare("SELECT {$list} FROM `{$copy}`{$where} ORDER BY 1");
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            yield $row;
        }
    }

    /** People in an archive, shaped like Announcer::recipients() so they can be written to. */
    public static function recipients(string $slug): array
    {
        if (self::find($slug) === null) {
            return [];
        }
        $copy = self::table($slug, 'registrations');

        return Database::connection()->query(
            "SELECT reference, first_name, last_name, email, kingschat_username, participation
             FROM `{$copy}` WHERE status = 'confirmed' AND email NOT LIKE '%@loadtest.invalid' ORDER BY created_at"
        )->fetchAll() ?: [];
    }

    /** The archived registrations table, for joins elsewhere. */
    public static function registrationsTable(string $slug): string
    {
        return self::table($slug, 'registrations');
    }

    /** Table names are built only from a checked slug and the fixed list above. */
    private static function table(string $slug, string $table): string
    {
        if (!preg_match('/^[a-z0-9_]{1,24}$/', $slug) || !isset(self::TABLES[$table])) {
            throw new \InvalidArgumentException('Unknown archive table.');
        }

        return "archive_{$slug}_{$table}";
    }

    private static function exists(string $table): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return array<int,string> */
    private static function columns(string $copy): array
    {
        if (!self::exists($copy)) {
            return [];
        }
        $names = Database::connection()->query("SHOW COLUMNS FROM `{$copy}`")->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_diff($names, self::HIDDEN));
    }

    /** A search looks in every text column. @return array{0:string,1:array<int,string>} */
    private static function where(string $copy, string $search): array
    {
        $search = trim($search);
        if ($search === '' || !self::exists($copy)) {
            return ['', []];
        }
        $text = [];
        foreach (Database::connection()->query("SHOW COLUMNS FROM `{$copy}`")->fetchAll() as $c) {
            if (preg_match('/char|text/i', (string) $c['Type']) && !in_array($c['Field'], self::HIDDEN, true)) {
                $text[] = '`' . $c['Field'] . '`';
            }
        }
        if (!$text) {
            return ['', []];
        }

        return [' WHERE CONCAT_WS(\' \', ' . implode(', ', $text) . ') LIKE ?', ['%' . $search . '%']];
    }

    private static function decode(array $row): array
    {
        $row['counts'] = json_decode((string) $row['counts'], true) ?: [];

        return $row;
    }
}

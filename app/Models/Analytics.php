<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Traffic and presence.
 *
 * A visitor is identified by a hash of address, user agent and a salt that
 * changes every day, so the same person can be counted once within a day
 * without storing anything that identifies them or follows them beyond it.
 */
final class Analytics
{
    /** How long after its last heartbeat a session still counts as connected. */
    public const PRESENCE_WINDOW = 90;

    public static function visitorHash(string $ip, string $userAgent): string
    {
        return substr(hash_hmac('sha256', $ip . '|' . $userAgent, self::dailySalt()), 0, 32);
    }

    private static function dailySalt(): string
    {
        return (string) config('app.key') . '|' . gmdate('Y-m-d');
    }

    public static function device(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|headless|monitor|curl|wget|preview/', $ua)) {
            return 'bot';
        }
        if (str_contains($ua, 'ipad') || (str_contains($ua, 'android') && !str_contains($ua, 'mobile'))) {
            return 'tablet';
        }
        if (preg_match('/mobile|iphone|ipod|android/', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function record(string $path, string $visitorHash, string $sessionHash, string $device, ?string $referrer): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO page_views (visitor_hash, session_hash, path, referrer, device)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $visitorHash,
            $sessionHash,
            mb_substr($path, 0, 190),
            $referrer !== null ? mb_substr($referrer, 0, 190) : null,
            $device,
        ]);
    }

    /** Mark a session as still here. Called on page load and by the heartbeat. */
    public static function touch(
        string $sessionHash,
        string $visitorHash,
        string $path,
        string $device,
        string $context = 'site',
        ?string $reference = null
    ): void {
        $stmt = Database::connection()->prepare(
            'INSERT INTO presence (session_hash, visitor_hash, path, context, reference, device)
             VALUES (:session, :visitor, :path, :context, :reference, :device)
             ON DUPLICATE KEY UPDATE
                path = VALUES(path), context = VALUES(context),
                reference = VALUES(reference), last_seen_at = NOW()'
        );
        $stmt->execute([
            'session'   => $sessionHash,
            'visitor'   => $visitorHash,
            'path'      => mb_substr($path, 0, 190),
            'context'   => $context,
            'reference' => $reference,
            'device'    => $device,
        ]);
    }

    public static function leave(string $sessionHash): void
    {
        Database::connection()
            ->prepare('DELETE FROM presence WHERE session_hash = ?')
            ->execute([$sessionHash]);
    }

    /** @return array{site:int, watch:int, visitors:int} */
    public static function connectedNow(): array
    {
        $row = Database::connection()->query(
            'SELECT
                SUM(context = "site")  AS site,
                SUM(context = "watch") AS watch,
                COUNT(DISTINCT visitor_hash) AS visitors
             FROM presence
             WHERE device <> "bot" AND last_seen_at > (NOW() - INTERVAL ' . self::PRESENCE_WINDOW . ' SECOND)'
        )->fetch() ?: [];

        return [
            'site'     => (int) ($row['site'] ?? 0),
            'watch'    => (int) ($row['watch'] ?? 0),
            'visitors' => (int) ($row['visitors'] ?? 0),
        ];
    }

    /** Everyone currently in the stream, newest arrival first. */
    public static function watchers(): array
    {
        $stmt = Database::connection()->query(
            'SELECT p.session_hash, p.reference, p.device, p.started_at, p.last_seen_at,
                    r.first_name, r.last_name, r.email, r.participation
             FROM presence p
             LEFT JOIN registrations r ON r.reference = p.reference
             WHERE p.context = "watch"
               AND p.last_seen_at > (NOW() - INTERVAL ' . self::PRESENCE_WINDOW . ' SECOND)
             ORDER BY p.started_at DESC
             LIMIT 200'
        );

        return $stmt->fetchAll() ?: [];
    }

    /** Headline counts for a window of days, plus the same window before it. */
    public static function summary(int $days = 7): array
    {
        $sql = 'SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors, COUNT(DISTINCT session_hash) AS sessions
                FROM page_views
                WHERE device <> "bot" AND viewed_at >= (NOW() - INTERVAL :days DAY)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['days' => $days]);
        $now = $stmt->fetch() ?: [];

        $prev = Database::connection()->prepare(
            'SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
             FROM page_views
             WHERE device <> "bot"
               AND viewed_at < (NOW() - INTERVAL :days DAY)
               AND viewed_at >= (NOW() - INTERVAL :double DAY)'
        );
        $prev->execute(['days' => $days, 'double' => $days * 2]);
        $before = $prev->fetch() ?: [];

        return [
            'views'          => (int) ($now['views'] ?? 0),
            'visitors'       => (int) ($now['visitors'] ?? 0),
            'sessions'       => (int) ($now['sessions'] ?? 0),
            'views_before'   => (int) ($before['views'] ?? 0),
            'visitors_before'=> (int) ($before['visitors'] ?? 0),
        ];
    }

    /** One row per day for the chart, with no gaps. */
    public static function daily(int $days = 14): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT DATE(viewed_at) AS day, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
             FROM page_views
             WHERE device <> "bot" AND viewed_at >= (NOW() - INTERVAL :days DAY)
             GROUP BY DATE(viewed_at)'
        );
        $stmt->execute(['days' => $days]);
        $rows = array_column($stmt->fetchAll() ?: [], null, 'day');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = gmdate('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'day'      => $day,
                'views'    => (int) ($rows[$day]['views'] ?? 0),
                'visitors' => (int) ($rows[$day]['visitors'] ?? 0),
            ];
        }

        return $series;
    }

    /** @return array<int,array{label:string,count:int}> */
    public static function breakdown(string $column, int $days = 7, int $limit = 8): array
    {
        $allowed = ['path', 'referrer', 'device', 'country'];
        if (!in_array($column, $allowed, true)) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(NULLIF(`{$column}`, ''), 'direct') AS label, COUNT(*) AS count
             FROM page_views
             WHERE device <> 'bot' AND viewed_at >= (NOW() - INTERVAL :days DAY)
             GROUP BY label
             ORDER BY count DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['days' => $days]);

        return array_map(
            static fn (array $r) => ['label' => (string) $r['label'], 'count' => (int) $r['count']],
            $stmt->fetchAll() ?: []
        );
    }

    /** Old rows are not useful and should not pile up. */
    public static function prune(int $keepDays = 180): void
    {
        Database::connection()->exec(
            'DELETE FROM page_views WHERE viewed_at < (NOW() - INTERVAL ' . $keepDays . ' DAY)'
        );
        Database::connection()->exec(
            'DELETE FROM presence WHERE last_seen_at < (NOW() - INTERVAL 1 DAY)'
        );
    }
}

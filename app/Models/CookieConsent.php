<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class CookieConsent
{
    public const ACTIONS = ['accept_all', 'essential_only', 'custom'];
    public const CATEGORIES = ['preferences', 'analytics', 'marketing'];
    public const POLICY_VERSION = '1.0';

    public static function record(
        string $action,
        bool $preferences,
        bool $analytics,
        bool $marketing,
        ?string $ipHash,
        string $userAgent,
    ): void {
        $stmt = Database::connection()->prepare(
            'INSERT INTO cookie_consents (action, preferences, analytics, marketing, policy_version, ip_hash, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $action,
            (int) $preferences,
            (int) $analytics,
            (int) $marketing,
            self::POLICY_VERSION,
            $ipHash,
            $userAgent,
        ]);
    }
}

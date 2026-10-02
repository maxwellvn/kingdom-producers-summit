#!/usr/bin/env php
<?php
/**
 * Email passes or live links to a whole group, over one mail connection.
 *   php bin/bulk-send.php pass onsite [manchester|ireland]
 *   php bin/bulk-send.php live online|onsite|all [manchester|ireland]
 * Started by the admin registrations page; progress lands in storage/bulk-send.json.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    // A separate process has no URL to read the event from, so the admin page passes it along.
    App\Core\Events::using((string) ($argv[3] ?? App\Core\Events::DEFAULT), static function () use ($argv): void {
        App\Services\BulkSender::run((string) ($argv[1] ?? 'live'), (string) ($argv[2] ?? 'online'));
    });
} catch (\Throwable $e) {
    App\Services\BulkSender::fail('The sender crashed: ' . $e->getMessage());
    throw $e;
}

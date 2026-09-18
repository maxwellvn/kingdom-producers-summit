#!/usr/bin/env php
<?php
/**
 * Email passes or live links to a whole group, over one mail connection.
 *   php bin/bulk-send.php pass onsite
 *   php bin/bulk-send.php live online|onsite|all
 * Started by the admin registrations page; progress lands in storage/bulk-send.json.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    App\Services\BulkSender::run((string) ($argv[1] ?? 'live'), (string) ($argv[2] ?? 'online'));
} catch (\Throwable $e) {
    App\Services\BulkSender::fail('The sender crashed: ' . $e->getMessage());
    throw $e;
}

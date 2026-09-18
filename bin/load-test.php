#!/usr/bin/env php
<?php
/**
 * Simulate viewers on the watch page and measure the server.
 *   php bin/load-test.php <viewers> <seconds>
 * Started by the admin stream page; can also be run by hand.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    App\Services\LoadTester::run((int) ($argv[1] ?? 25), (int) ($argv[2] ?? 60));
} catch (\Throwable $e) {
    App\Services\LoadTester::fail('The runner crashed: ' . $e->getMessage());
    throw $e;
}

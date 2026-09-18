<?php
/**
 * Sends every announcement whose time has come. Run it once a minute:
 *   * * * * * php /path/to/bin/send-due.php
 * The Docker entrypoint loops it for you; the admin "send now" button includes it too.
 */
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require __DIR__ . '/../app/bootstrap.php';
}

use App\Models\Announcement;
use App\Services\Announcer;

while (($a = Announcement::claimDue()) !== null) {
    $r = Announcer::send((string) $a['audience'], (string) $a['subject'], (string) $a['body'], (bool) $a['by_email'], (bool) $a['by_kingschat']);
    $summary = Announcer::summary($r);
    Announcement::finish((int) $a['id'], $summary);
    if (PHP_SAPI === 'cli') {
        echo date('c') . " #{$a['id']} {$summary}\n";
    }
}

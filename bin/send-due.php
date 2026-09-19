<?php
/**
 * Sends every announcement whose time has come, one person at a time, and keeps going on
 * later runs until everyone has had theirs. Run it once a minute:
 *   * * * * * php /path/to/bin/send-due.php
 * The Docker entrypoint loops it for you; ordinary page traffic triggers it as well.
 */
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require __DIR__ . '/../app/bootstrap.php';
}

use App\Models\Announcement;
use App\Services\Announcer;

// Pick up anything newly due, then anything still in flight from earlier runs.
while (Announcement::claimDue() !== null) {
}
foreach (Announcement::inFlight() as $a) {
    Announcer::enqueue($a);
    $state = Announcer::drain($a);
    $progress = Announcer::progress((int) $a['id']);
    if ($state === 'done') {
        Announcement::finish((int) $a['id'], Announcer::summary($progress));
    }
    if (PHP_SAPI === 'cli') {
        echo date('c') . " #{$a['id']} {$state}: {$progress['sent']}/{$progress['total']} sent, {$progress['failed']} failed\n";
    }
    if ($state === 'throttled') {
        break; // the host asked us to slow down; every announcement waits for the next minute
    }
}

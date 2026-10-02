#!/usr/bin/env php
<?php
/**
 * Before registrations knew their event, Ireland sign-ups were saved as Manchester.
 * This lists the likely ones (since the chooser went live: country Ireland or a +353 phone)
 * so an organiser can confirm them, then moves only the references named.
 *   php bin/likely-ireland.php                     list, change nothing
 *   php bin/likely-ireland.php --move KPS26-ABC123 KPS26-DEF456
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

const SINCE = '2026-09-29 00:00:00';

$pdo = Database::connection();
$args = array_slice($argv, 1);

if (($args[0] ?? '') !== '--move') {
    $stmt = $pdo->prepare(
        "SELECT reference, first_name, last_name, email, phone, country, participation, created_at
         FROM registrations
         WHERE event = 'manchester' AND created_at >= ?
           AND (country = 'Ireland' OR REPLACE(phone, ' ', '') LIKE '+353%' OR REPLACE(phone, ' ', '') LIKE '00353%')
         ORDER BY created_at"
    );
    $stmt->execute([SINCE]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        printf("%s  %-28s %-34s %-16s %-14s %s %s\n", $r['reference'], $r['first_name'] . ' ' . $r['last_name'], $r['email'], $r['phone'], $r['country'], $r['participation'], $r['created_at']);
    }
    echo count($rows) . " likely Ireland registration(s). Nothing changed.\n";
    exit(0);
}

$move = $pdo->prepare("UPDATE registrations SET event = 'ireland' WHERE reference = ? AND event = 'manchester'");
foreach (array_slice($args, 1) as $reference) {
    try {
        $move->execute([strtoupper($reference)]);
        echo $reference . ($move->rowCount() ? " moved to Ireland\n" : " not found as a Manchester registration\n");
    } catch (PDOException $e) {
        // Same email already registered for Ireland: leave both as they are for a person to decide.
        echo $reference . ' not moved: ' . ($e->getCode() === '23000' ? 'that email is already registered for Ireland' : $e->getMessage()) . "\n";
    }
}

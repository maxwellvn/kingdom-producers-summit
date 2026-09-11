#!/usr/bin/env php
<?php
/**
 * Send the "your place is not yet secured" reminder to anyone unpaid for a
 * day. Safe to run as often as you like; each person is reminded once.
 *   php bin/remind-unpaid.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$sent = App\Services\PaymentReminders::run();
$released = App\Services\PaymentReminders::release();
echo "Sent {$sent} reminder(s), released {$released} place(s).\n";

<?php
/** Integration regression checks. All database changes are rolled back. */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Registration;
use App\Services\AttendanceService;
use App\Services\RegistrationService;

function verify(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
    echo "PASS: {$label}\n";
}
$pdo = Database::connection();
$pdo->beginTransaction();
try {
    $incomplete = new Request('POST', '/register', [], [
        'participation' => 'online', 'first_name' => 'Missing', 'last_name' => 'Details',
        'email' => 'required-' . bin2hex(random_bytes(8)) . '@example.org',
        'country' => 'United Kingdom', 'age_band' => '25-34',
        'field' => Registration::FIELDS[0], 'producer_stage' => 'emerge', 'consent_terms' => '1',
    ], []);
    $service = new RegistrationService();
    [$requiredErrors] = $service->validate($incomplete);
    verify(isset($requiredErrors['phone'], $requiredErrors['zone']), 'phone and zone are required');
    verify(!isset($requiredErrors['group_name'], $requiredErrors['church_name']), 'group and church are optional');

    $request = new Request('POST', '/register', [], [
        'participation' => 'onsite', 'first_name' => 'Regression', 'last_name' => 'Test',
        'email' => 'attendance-' . bin2hex(random_bytes(8)) . '@example.org',
        'phone' => '+447700900123', 'country' => 'United Kingdom', 'age_band' => '25-34',
        'zone' => 'UK Zone 1',
        'field' => Registration::FIELDS[0], 'producer_stage' => 'build',
        'interests' => ['technology', 'other'], 'interest_other' => 'Sustainable transport',
        'consent_terms' => '1',
    ], []);
    [$errors, $clean] = $service->validate($request);
    verify($errors === [], 'test registration validates');
    verify($clean['interest_other'] === 'Sustainable transport', 'other area of interest is retained');
    $row = $service->register($clean);
    $ref = $row['reference'];
    $token = AttendanceService::tokenFor($ref);
    $scan = fn($code) => AttendanceService::checkIn($code, 'test@example.org', '127.0.0.1');
    verify(str_contains(RegistrationService::duplicateMessage(strtoupper($row['email'])), 'payment is still outstanding'), 'unpaid duplicate message is case insensitive');
    [$errors] = $service->validate($request);
    verify(str_contains($errors['email'] ?? '', 'payment is still outstanding'), 'duplicate form submission reports unpaid status');
    verify($scan($token)['ok'] === false, 'unpaid signed pass rejected');
    verify($scan(substr($token, 0, -1) . (str_ends_with($token, 'a') ? 'b' : 'a'))['ok'] === false, 'tampered QR rejected');
    verify(AttendanceService::referenceFromToken('https://example.org/admin/scanner?token=' . rawurlencode($token)) === $ref, 'copied scanner URL decodes');
    $update = $pdo->prepare('UPDATE registrations SET status = ?, payment_status = ?, participation = ? WHERE reference = ?');
    $update->execute(['confirmed', 'paid', 'onsite', $ref]);
    verify(str_contains(RegistrationService::duplicateMessage($row['email']), 'payment is complete'), 'paid duplicate message');
    verify($scan($token)['status'] === 'checked_in', 'paid signed pass records attendance');
    verify($scan($token)['status'] === 'duplicate', 'repeat scan is duplicate');
    $count = $pdo->prepare('SELECT COUNT(*) FROM attendances WHERE registration_id = ?');
    $count->execute([$row['id']]);
    verify((int) $count->fetchColumn() === 1, 'repeat scan creates no extra record');
    Session::put('admin_email', 'test@example.org');
    $response = (new App\Controllers\AdminController())->checkIn(new Request('POST', '/admin/check-in', [], ['token' => strtolower($ref)], []));
    $body = (new ReflectionProperty($response, 'body'))->getValue($response);
    verify(json_decode($body, true)['status'] === 'duplicate', 'manual reference uses same attendance record');
    $update->execute(['cancelled', 'paid', 'onsite', $ref]);
    verify($scan($token)['ok'] === false, 'cancelled pass rejected');
    verify(str_contains(RegistrationService::duplicateMessage($row['email']), 'cancelled'), 'cancelled duplicate message');
    $update->execute(['confirmed', 'not_required', 'onsite', $ref]);
    verify($scan($token)['ok'] === true, 'legacy free onsite pass remains valid');
    $update->execute(['confirmed', 'not_required', 'online', $ref]);
    verify(str_contains(RegistrationService::duplicateMessage($row['email']), 'No payment is required'), 'free online duplicate message');
    // Online and initiative places are not places in the room, so they never check in.
    $update->execute(['confirmed', 'paid', 'online', $ref]);
    verify($scan($token)['ok'] === false, 'an online pass is refused at the door');
    verify(str_contains($scan($token)['message'], 'not an onsite place'), 'the door says why an online pass is refused');
    $update->execute(['confirmed', 'not_required', 'initiative', $ref]);
    verify($scan($token)['ok'] === false, 'an initiative pass is refused at the door');
    $update->execute(['confirmed', 'paid', 'onsite', $ref]);
    Session::forget('admin_authenticated');
    verify((new App\Middleware\RequireAdmin())->handle($request) !== null, 'attendance requires staff authentication');
    verify(!Session::verifyCsrf('incorrect'), 'incorrect CSRF token rejected');

    // ---- The protected stream ----
    $watcher = $service->register($service->validateIssued(new Request('POST', '/admin/issue', [], [
        'participation' => 'online', 'first_name' => 'Stream', 'last_name' => 'Viewer',
        'email' => 'viewer-' . bin2hex(random_bytes(6)) . '@example.org',
        'kingschat_username' => 'streamviewer',
    ], []), 'organiser@example.org')[1]);

    verify(App\Services\StreamService::mayWatch($watcher), 'a confirmed online place may watch');
    verify(App\Services\StreamService::findViewer($watcher['reference'], $watcher['email']) !== null,
        'reference with the registered email opens the gate');
    verify(App\Services\StreamService::findViewer($watcher['reference'], 'streamviewer') !== null,
        'reference with the KingsChat handle opens the gate');
    verify(App\Services\StreamService::findViewer($watcher['reference'], 'someone@else.org') === null,
        'a reference alone is not enough');
    verify(App\Services\StreamService::findViewer('KPS26-NOSUCH', $watcher['email']) === null,
        'an unknown reference is refused');

    // Initiative members did not register for the summit.
    $pdo->prepare('UPDATE registrations SET participation = ? WHERE reference = ?')
        ->execute(['initiative', $watcher['reference']]);
    verify(!App\Services\StreamService::mayWatch(Registration::findByReference($watcher['reference'])),
        'an initiative place may not watch');
    $pdo->prepare('UPDATE registrations SET participation = ?, payment_status = ? WHERE reference = ?')
        ->execute(['online', 'unpaid', $watcher['reference']]);
    verify(!App\Services\StreamService::mayWatch(Registration::findByReference($watcher['reference'])),
        'an unpaid place may not watch');
    $pdo->prepare('UPDATE registrations SET payment_status = ? WHERE reference = ?')
        ->execute(['paid', $watcher['reference']]);

    // One viewer at a time: the newest device takes the pass.
    $first = str_repeat('a', 32);
    $second = str_repeat('b', 32);
    App\Services\StreamService::claimPass($watcher['reference'], $first, 'desktop', 'hash');
    verify(App\Services\StreamService::holdsPass($watcher['reference'], $first), 'the first device holds the pass');
    $claim = App\Services\StreamService::claimPass($watcher['reference'], $second, 'mobile', 'hash');
    verify($claim['takenOver'] === true, 'a second device reports the takeover');
    verify(App\Services\StreamService::holdsPass($watcher['reference'], $second), 'the second device now holds it');
    verify(!App\Services\StreamService::holdsPass($watcher['reference'], $first), 'the first device has lost it');
    App\Services\StreamService::releasePass($watcher['reference'], $second);
    verify(!App\Services\StreamService::holdsPass($watcher['reference'], $second), 'signing out releases the pass');

    // The stream link is read for what it is.
    verify(App\Services\StreamService::kind('https://cdn.example.org/live/index.m3u8') === 'hls', 'an m3u8 link is HLS');
    verify(App\Services\StreamService::kind('https://youtu.be/abc123') === 'iframe', 'a YouTube link is embedded');
    verify(App\Services\StreamService::kind('https://example.org/clip.mp4') === 'file', 'an mp4 link is a file');

    // A place issued by an organiser is settled: confirmed, nothing to pay.
    $issued = new Request('POST', '/admin/issue', [], [
        'participation' => 'onsite', 'first_name' => 'Guest', 'last_name' => 'Speaker',
        'email' => 'issued-' . bin2hex(random_bytes(8)) . '@example.org',
        'note' => 'Guest speaker',
    ], []);
    [$issueErrors, $issueClean] = $service->validateIssued($issued, 'organiser@example.org');
    verify($issueErrors === [], 'an issued place needs only a name and an email');
    verify($issueClean['status'] === 'confirmed' && $issueClean['payment_status'] === 'not_required',
        'an issued place is confirmed with nothing to pay');
    verify($issueClean['issued_by'] === 'organiser@example.org', 'the issuing organiser is recorded');
    $issuedRow = $service->register($issueClean);
    verify(AttendanceService::checkIn(AttendanceService::tokenFor($issuedRow['reference']), 'gate@example.org', '127.0.0.1')['status'] === 'checked_in',
        'an issued pass scans at the gate');

    // The same email cannot be issued twice.
    [$dupErrors] = $service->validateIssued(new Request('POST', '/admin/issue', [], [
        'participation' => 'online', 'first_name' => 'Guest', 'last_name' => 'Again',
        'email' => $issuedRow['email'],
    ], []), 'organiser@example.org');
    verify(isset($dupErrors['email']), 'an issued email cannot be reused');

    // Initiative sign-ups collect personal details only: no field, no producer stage.
    $initiative = new Request('POST', '/register', [], [
        'participation' => 'initiative', 'first_name' => 'Initiative', 'last_name' => 'Only',
        'email' => 'initiative-' . bin2hex(random_bytes(8)) . '@example.org',
        'phone' => '+447700900321', 'country' => 'United Kingdom', 'age_band' => '25-34',
        'zone' => 'UK Zone 1', 'group_name' => 'Essex Group', 'church_name' => 'Rainham Church',
        'consent_terms' => '1',
    ], []);
    [$initiativeErrors, $initiativeClean] = $service->validate($initiative);
    verify($initiativeErrors === [], 'initiative registration needs no field or stage');
    verify($initiativeClean['field'] === null && $initiativeClean['producer_stage'] === null, 'initiative registration stores no field or stage');

    // Choosing "Other" as the field keeps whatever the registrant typed.
    $otherField = new Request('POST', '/register', [], [
        'participation' => 'online', 'first_name' => 'Other', 'last_name' => 'Field',
        'email' => 'other-field-' . bin2hex(random_bytes(8)) . '@example.org',
        'phone' => '+447700900654', 'country' => 'United Kingdom', 'age_band' => '25-34',
        'zone' => 'UK Zone 1', 'group_name' => 'Essex Group', 'church_name' => 'Rainham Church',
        'field' => 'Other', 'field_other' => 'Sport and recreation',
        'producer_stage' => 'build', 'consent_terms' => '1',
    ], []);
    [$otherErrors, $otherClean] = $service->validate($otherField);
    verify($otherErrors === [], 'other field registration validates');
    verify($otherClean['field_other'] === 'Sport and recreation', 'the typed field is kept');
    verify(field_label($otherClean) === 'Sport and recreation (other)', 'the typed field is what gets displayed');

    // A named field discards any leftover text from the "Other" box.
    $namedField = new Request('POST', '/register', [], array_merge($otherField->all(), [
        'email' => 'named-field-' . bin2hex(random_bytes(8)) . '@example.org',
        'field' => Registration::FIELDS[0],
    ]), []);
    [, $namedClean] = $service->validate($namedField);
    verify($namedClean['field_other'] === null, 'a named field stores no other text');

    // Onsite places are capped, and the cap is enforced on the server, not just in the markup.
    $capacity = max(1, (int) config('app.summit.onsite_capacity'));
    $pdo->exec("UPDATE registrations SET status = 'confirmed' WHERE participation = 'onsite'");
    verify(Registration::onsiteSeatsTaken() < $capacity, 'onsite capacity is not already exhausted');
    $filler = $pdo->prepare('INSERT INTO registrations (reference, participation, first_name, last_name, email, country, age_band, status, payment_status) '
        . "VALUES (?, 'onsite', 'Seat', 'Filler', ?, 'United Kingdom', '25-34', 'confirmed', 'paid')");
    for ($i = Registration::onsiteSeatsTaken(); $i < $capacity; $i++) {
        $filler->execute(['KPS26-F' . str_pad((string) $i, 5, '0', STR_PAD_LEFT), "seat-{$i}-" . bin2hex(random_bytes(4)) . '@example.org']);
    }
    verify(!RegistrationService::onsitePlaceAvailable(), 'onsite capacity reports full');
    $overflow = new Request('POST', '/register', [], [
        'participation' => 'onsite', 'first_name' => 'One', 'last_name' => 'TooMany',
        'email' => 'overflow-' . bin2hex(random_bytes(8)) . '@example.org',
        'phone' => '+447700900999', 'country' => 'United Kingdom', 'age_band' => '25-34',
        'zone' => 'UK Zone 1', 'group_name' => 'Essex Group', 'church_name' => 'Rainham Church',
        'field' => Registration::FIELDS[0], 'producer_stage' => 'build', 'consent_terms' => '1',
    ], []);
    [$overflowErrors] = $service->validate($overflow);
    verify(str_contains($overflowErrors['participation'] ?? '', 'fully booked'), 'onsite registration is refused when full');
} finally {
    $pdo->rollBack();
}
echo "All checks passed; test data rolled back.\n";

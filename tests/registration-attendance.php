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
    verify(isset($requiredErrors['phone'], $requiredErrors['zone'], $requiredErrors['group_name'], $requiredErrors['church_name']), 'phone and church hierarchy are required');

    $request = new Request('POST', '/register', [], [
        'participation' => 'onsite', 'first_name' => 'Regression', 'last_name' => 'Test',
        'email' => 'attendance-' . bin2hex(random_bytes(8)) . '@example.org',
        'phone' => '+447700900123', 'country' => 'United Kingdom', 'age_band' => '25-34',
        'zone' => 'UK Zone 1', 'group_name' => 'Essex Group', 'church_name' => 'Rainham Church',
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
    Session::forget('admin_authenticated');
    verify((new App\Middleware\RequireAdmin())->handle($request) !== null, 'attendance requires staff authentication');
    verify(!Session::verifyCsrf('incorrect'), 'incorrect CSRF token rejected');

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

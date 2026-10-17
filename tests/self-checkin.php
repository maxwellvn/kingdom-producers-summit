<?php
/** Self check-in from the desk QR. All database changes are rolled back. */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Controllers\CheckInController;
use App\Controllers\RegistrationController;
use App\Core\Database;
use App\Core\Events;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\RegistrationService;

function verify(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
    echo "PASS: {$label}\n";
}
function body(Response $r): string { return (new ReflectionProperty($r, 'body'))->getValue($r); }
function location(Response $r): string { return (new ReflectionProperty($r, 'headers'))->getValue($r)['Location'] ?? ''; }

function attended(int $id): int {
    $s = Database::connection()->prepare('SELECT COUNT(*) FROM attendances WHERE registration_id = ?');
    $s->execute([$id]);
    return (int) $s->fetchColumn();
}

$pdo = Database::connection();
$pdo->beginTransaction();
try {
    $service = new RegistrationService();
    $make = static function (string $participation) use ($service): array {
        [$errors, $clean] = $service->validate(new Request('POST', '/register', [], [
            'participation' => $participation, 'first_name' => 'Desk', 'last_name' => 'Walker',
            'email' => 'selfcheck-' . bin2hex(random_bytes(6)) . '@example.org', 'phone' => '+447700900123',
            'country' => 'United Kingdom', 'age_band' => '25-34', 'zone' => 'Zone 1',
            'field' => Registration::FIELDS[0], 'producer_stage' => 'build', 'consent_terms' => '1',
        ], []));
        if ($errors) throw new RuntimeException('fixture did not validate: ' . json_encode($errors));
        return $service->register($clean);
    };
    $c = new CheckInController();
    $get = static fn (array $q = []) => new Request('GET', '/checkin', $q, [], []);
    $post = static fn (array $b) => new Request('POST', '/checkin', [], $b, []);

    Setting::set('checkin_open', '0');
    verify(str_contains(body($c->show($get())), 'Self check-in is not open right now'), 'while self check-in is off the page sends people to the desk');

    Setting::set('checkin_open', '1');
    verify(CheckInController::allowed(), 'turning self check-in on opens the page');
    verify(str_contains(body($c->show($get())), 'Register and check in'), 'the page offers registration to walk-ins');

    $unknown = body($c->store($post(['identifier' => 'nobody-' . bin2hex(random_bytes(4)) . '@example.org'])));
    verify(str_contains($unknown, 'You are not registered yet') && str_contains($unknown, 'Register now'), 'an unknown email gets a register button');

    $online = $make('online');
    $done = $c->store($post(['identifier' => strtoupper($online['email'])]));
    verify(str_ends_with(location($done), '/checkin/welcome'), 'a registered email checks in and goes to the welcome screen');
    verify(attended((int) $online['id']) === 1, 'the check-in is recorded once');
    verify(Registration::findByReference($online['reference'])['participation'] === 'onsite', 'an online registrant who walked in becomes onsite');
    $welcome = body($c->welcome($get()));
    verify(str_contains($welcome, 'Show this screen to the official at the registration desk'), 'the welcome screen tells them to show the desk');
    verify(str_contains($welcome, 'Welcome,<br>Desk.') && str_contains($welcome, $online['reference']), 'the welcome screen shows their name and reference');

    // Same phone again: recognised, one tap, still one record.
    verify(str_contains(body($c->show($get())), 'Check me in'), 'a recognised phone gets the one-tap button');
    verify(str_ends_with(location($c->store($post(['me' => '1']))), '/checkin/welcome'), 'one tap checks in the recognised person');
    verify(attended((int) $online['id']) === 1, 'checking in twice makes no extra record');

    $initiative = $make('initiative');
    verify(str_contains(body($c->store($post(['identifier' => $initiative['email']]))), 'see the official at the desk'), 'an initiative registration is sent to the desk');
    verify(attended((int) $initiative['id']) === 0, 'an initiative registration is not checked in');

    $walkIn = $make('onsite');
    $plain = $make('onsite');
    verify(CheckInController::afterRegistering(new Request('POST', '/register', [], [], []), $plain) === null, 'registering from the ordinary form does not check in');
    verify(attended((int) $plain['id']) === 0, 'the ordinary registrant is not recorded as attended');
    verify(str_contains(body($c->store($post(['identifier' => 'nobody-' . bin2hex(random_bytes(4)) . '@example.org']))), 'checkin=1'), 'the register button carries the check-in flag');

    $fromDesk = static fn () => new Request('POST', '/register', [], ['checkin' => '1'], []);
    verify(CheckInController::afterRegistering($fromDesk(), $walkIn) === '/checkin/welcome', 'registering from the desk QR checks in at once');
    verify(attended((int) $walkIn['id']) === 1, 'the walk-in is recorded as attended');

    // Registered through the ordinary form at the venue: the pass page offers check-in, and the tap does it.
    Session::put('last_registration', $plain['reference']);
    $confirmed = static fn () => body((new RegistrationController())->confirmed(new Request('GET', '/register/confirmed', [], [], [])));
    verify(str_contains($confirmed(), 'Already at the venue?'), 'the pass page offers check-in to an onsite registrant');
    verify(str_ends_with(location($c->store($post(['me' => '1']))), '/checkin/welcome') && attended((int) $plain['id']) === 1, 'tapping check in from the pass page checks them in');

    $ireland = Events::using('ireland', static fn () => $make('onsite'));
    verify(str_contains(body($c->store($post(['identifier' => $ireland['email']]))), 'You are not registered yet'), 'a registration for the other event is not found here');

    Setting::set('checkin_open', '0');
    verify(str_contains(body($c->store($post(['me' => '1']))), 'Self check-in is not open right now'), 'turning it off stops check-ins');
    verify(!str_contains($confirmed(), 'Already at the venue?'), 'the pass page offers no check-in while it is closed');
    verify(CheckInController::afterRegistering($fromDesk(), $make('onsite')) === null, 'registering while it is off shows the normal pass');

    echo "All checks passed; test data rolled back.\n";
} finally {
    $pdo->rollBack();
}

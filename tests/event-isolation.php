<?php
/** Manchester and Ireland must never mix. All database changes are rolled back. */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\Database;
use App\Core\Events;
use App\Core\Request;
use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Registration;
use App\Services\Announcer;
use App\Services\AttendanceService;
use App\Services\RegistrationService;
use App\Services\StreamService;

function verify(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
    echo "PASS: {$label}\n";
}
$pdo = Database::connection();
$pdo->beginTransaction();
try {
    $email = 'isolation-' . bin2hex(random_bytes(6)) . '@example.org';
    $register = static function (string $event) use ($email): array {
        return Events::using($event, static function () use ($email): array {
            $service = new RegistrationService();
            [$errors, $clean] = $service->validate(new Request('POST', '/register', [], [
                'participation' => 'onsite', 'first_name' => 'Iso', 'last_name' => ucfirst(Events::active()),
                'email' => $email, 'phone' => '+447700900123', 'country' => 'United Kingdom', 'age_band' => '25-34',
                'zone' => 'Zone 1', 'field' => Registration::FIELDS[0], 'producer_stage' => 'build', 'consent_terms' => '1',
            ], []));
            if ($errors !== []) throw new RuntimeException('validation: ' . json_encode($errors));

            return $service->register($clean);
        });
    };

    $man = $register('manchester');
    $ire = $register('ireland');
    verify($man['event'] === 'manchester' && $ire['event'] === 'ireland', 'each registration is tagged with the event it came from');
    verify($man['reference'] !== $ire['reference'], 'the same email gets a separate place at each event');

    Events::using('manchester', static function () use ($email): void {
        $service = new RegistrationService();
        [$errors] = $service->validate(new Request('POST', '/register', [], ['email' => $email], []));
        verify(str_contains($errors['email'] ?? '', 'already registered'), 'a second sign-up for the same event is still refused');
    });

    $scan = static fn (string $event, array $row): array => Events::using($event, static fn () => AttendanceService::checkIn(AttendanceService::tokenFor($row['reference']), 'test@example.org', '127.0.0.1'));
    $wrong = $scan('ireland', $man);
    verify($wrong['ok'] === false && $wrong['status'] === 'wrong_event' && str_contains($wrong['message'], 'Manchester'), 'the Ireland desk refuses a Manchester pass and says where it belongs');
    verify($scan('manchester', $man)['status'] === 'checked_in', 'the Manchester desk admits it');
    verify(Events::using('ireland', static fn () => Registration::attendanceStats()['total']) === 0, 'Ireland check-in count is untouched');

    verify(Events::using('manchester', static fn () => StreamService::mayWatch($man)), 'a Manchester pass opens the Manchester stream');
    verify(!Events::using('ireland', static fn () => StreamService::mayWatch($man)), 'a Manchester pass does not open the Ireland stream');

    $refsFor = static fn (string $event): array => array_column(Events::using($event, static fn () => Announcer::recipients('all')), 'reference');
    verify(in_array($man['reference'], $refsFor('manchester'), true) && !in_array($ire['reference'], $refsFor('manchester'), true), 'a Manchester notification reaches Manchester people only');
    verify(in_array($ire['reference'], $refsFor('ireland'), true) && !in_array($man['reference'], $refsFor('ireland'), true), 'an Ireland notification reaches Ireland people only');

    Events::using('ireland', static fn () => Comment::add($ire['reference'], 'Iso Ireland', 'hello from Ireland'));
    $bodies = static fn (string $event): array => array_column(Events::using($event, static fn () => Comment::recent()), 'body');
    verify(in_array('hello from Ireland', $bodies('ireland'), true) && !in_array('hello from Ireland', $bodies('manchester'), true), 'chat stays in its own event');

    $id = Events::using('ireland', static fn () => Announcement::create(['audience' => 'all', 'subject' => 'Iso', 'body' => 'x', 'by_email' => 1, 'by_kingschat' => 0, 'send_at' => '2099-01-01 00:00:00', 'created_by' => 'test']));
    verify(in_array($id, array_map('intval', array_column(Events::using('ireland', static fn () => Announcement::recent()), 'id')), true)
        && !in_array($id, array_map('intval', array_column(Events::using('manchester', static fn () => Announcement::recent()), 'id')), true), 'notifications are listed under their own event');

    verify(Events::using('ireland', static fn () => Registration::find((int) $man['id'])) === null, 'admin switched to Ireland cannot open a Manchester registration');
    verify(Events::using('ireland', static fn () => config('app.summit.edition')) === 'Ireland Edition 2026'
        && Events::using('manchester', static fn () => config('app.summit.edition')) !== 'Ireland Edition 2026', 'each event reads its own edition details');

    echo "All checks passed; test data rolled back.\n";
} finally {
    $pdo->rollBack();
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class Registration
{
    public const PARTICIPATION = ['onsite', 'online', 'initiative'];
    public const STAGES = ['emerge', 'build', 'establish', 'multiply'];
    public const AGE_BANDS = ['under18', '18-24', '25-34', '35-44', '45-54', '55-64', '65plus'];

    public const FIELDS = [
        'Accounting & Finance', 'Agriculture & Food', 'Architecture & Interior Design',
        'Automotive & Transport', 'Beauty & Personal Care', 'Charity & Social Care',
        'Consulting & Professional Services', 'Education & Training', 'Energy & Utilities',
        'Events & Production', 'Fashion & Design', 'Finance & Investment',
        'Health & Wellbeing', 'Hospitality, Travel & Tourism', 'Human Resources & Recruitment',
        'Import, Export & Trade', 'Insurance', 'Law & Legal Services',
        'Logistics & Supply Chain', 'Manufacturing & Engineering', 'Marketing, Advertising & PR',
        'Media & Film', 'Ministry & Community', 'Music & Performing Arts',
        'Photography & Videography', 'Property & Construction', 'Public Service & Policy',
        'Publishing & Writing', 'Retail & E-commerce', 'Science & Research',
        'Security & Facilities', 'Sports & Fitness', 'Technology & Software',
        'Student', 'Not working right now', 'Other',
    ];

    public const INTERESTS = [
        'technology'    => 'Technology & software',
        'media'         => 'Media & film',
        'music'         => 'Music & performing arts',
        'fashion'       => 'Fashion & design',
        'manufacturing' => 'Manufacturing & engineering',
        'agriculture'   => 'Agriculture & food',
        'finance'       => 'Finance & investment',
        'property'      => 'Property & construction',
        'health'        => 'Health & wellbeing',
        'education'     => 'Education & training',
        'publishing'    => 'Publishing & writing',
        'retail'        => 'Retail & e-commerce',
        'ministry'      => 'Ministry & community',
        'public_service'=> 'Public service & policy',
        'other'         => 'Another area not listed',
    ];

    public const CONTRIBUTE = [
        'mentor'    => 'Mentor other producers',
        'speaker'   => 'Speak or teach',
        'showcase'  => 'Showcase a product',
        'volunteer' => 'Volunteer at events',
        'partner'   => 'Partner or sponsor',
        'research'  => 'Contribute research & case studies',
    ];

    public const HEAR_ABOUT = [
        'church' => 'Church / Zone announcement', 'kingschat' => 'KingsChat', 'social' => 'Social media',
        'friend' => 'A friend or colleague', 'email' => 'Email', 'poster' => 'Poster or flyer', 'other' => 'Other',
    ];

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM registrations WHERE email = ? LIMIT 1');
        $stmt->execute([mb_strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    /** Handles are stored without the leading @, but match either way. */
    public static function findByKingsChatUsername(string $username): ?array
    {
        $username = ltrim(mb_strtolower(trim($username)), '@');
        if ($username === '') {
            return null;
        }
        // Match whether or not the stored handle kept its leading @.
        $stmt = Database::connection()->prepare(
            'SELECT * FROM registrations WHERE LOWER(kingschat_username) IN (?, ?) LIMIT 1'
        );
        $stmt->execute([$username, '@' . $username]);
        return $stmt->fetch() ?: null;
    }

    public static function referenceExists(string $reference): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM registrations WHERE reference = ? LIMIT 1');
        $stmt->execute([$reference]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO registrations (%s) VALUES (%s)',
            implode(', ', array_map(static fn (string $c) => "`{$c}`", $columns)),
            implode(', ', $placeholders)
        );

        $pdo = Database::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $pdo->lastInsertId();
    }

    public static function findByReference(string $reference): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM registrations WHERE reference = ? LIMIT 1');
        $stmt->execute([$reference]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM registrations WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Registrant says they sent an offline payment (Espees / bank). Awaiting admin confirmation. */
    public static function claimPayment(string $reference, string $method): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE registrations
             SET payment_status = 'claimed', payment_method = ?
             WHERE reference = ? AND payment_status = 'unpaid'"
        );
        $stmt->execute([$method, $reference]);
        return $stmt->rowCount() > 0;
    }

    public static function setPaymentSession(string $reference, string $sessionId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE registrations SET payment_session_id = ? WHERE reference = ?'
        );
        $stmt->execute([$sessionId, $reference]);
    }

    /** Transition unpaid (or claimed offline payment) → paid. Returns false when already paid (idempotent). */
    public static function markPaid(string $reference, string $sessionId, int $amountPence): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE registrations
             SET payment_status = 'paid', status = 'confirmed', payment_amount = ?, payment_session_id = ?
             WHERE reference = ? AND payment_status IN ('unpaid', 'claimed')"
        );
        $stmt->execute([$amountPence, mb_substr($sessionId, 0, 255), $reference]);
        return $stmt->rowCount() > 0;
    }

    /** Pending registration awaiting payment, matched by reference + email. */
    public static function findPayable(string $reference, string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM registrations
             WHERE reference = ? AND email = ?
               AND payment_status = 'unpaid' AND status = 'pending'
             LIMIT 1"
        );
        $stmt->execute([$reference, $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Onsite places already held: paid, claimed, or awaiting payment. Cancelled rows release their place. */
    public static function onsiteSeatsTaken(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM registrations WHERE participation = 'onsite' AND status <> 'cancelled'")
            ->fetchColumn();
    }

    /** @return array{total:int, onsite:int, online:int, initiative:int, today:int, countries:int} */
    public static function stats(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(participation = 'onsite') AS onsite,
                    SUM(participation = 'online') AS online,
                    SUM(participation = 'initiative') AS initiative,
                    SUM(DATE(created_at) = CURDATE()) AS today,
                    COUNT(DISTINCT country) AS countries
                FROM registrations WHERE status <> 'cancelled'";

        $row = Database::connection()->query($sql)->fetch() ?: [];
        return array_map('intval', $row + ['total' => 0, 'onsite' => 0, 'online' => 0, 'initiative' => 0, 'today' => 0, 'countries' => 0]);
    }

    /** @return array{today:int,total:int} */
    public static function attendanceStats(): array
    {
        $row = Database::connection()->query(
            "SELECT COUNT(*) AS total, SUM(DATE(checked_in_at) = CURDATE()) AS today FROM attendances"
        )->fetch() ?: [];

        return ['total' => (int) ($row['total'] ?? 0), 'today' => (int) ($row['today'] ?? 0)];
    }

    /** @return array{created:bool,checked_in_at:string} */
    public static function recordAttendance(int $registrationId, string $staffEmail, string $ipAddress): array
    {
        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO attendances (registration_id, checked_in_by, ip_address) VALUES (?, ?, ?)'
            );
            $stmt->execute([$registrationId, mb_substr($staffEmail, 0, 190), @inet_pton($ipAddress) ?: null]);
            return ['created' => true, 'checked_in_at' => date('Y-m-d H:i:s')];
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }

        $stmt = $pdo->prepare('SELECT checked_in_at FROM attendances WHERE registration_id = ? LIMIT 1');
        $stmt->execute([$registrationId]);
        return ['created' => false, 'checked_in_at' => (string) $stmt->fetchColumn()];
    }

    /** @return array<int, array{label:string, count:int}> */
    public static function byStage(): array
    {
        $rows = Database::connection()
            ->query("SELECT producer_stage AS label, COUNT(*) AS count FROM registrations WHERE status <> 'cancelled' GROUP BY producer_stage")
            ->fetchAll();
        $map = array_column($rows, 'count', 'label');
        return array_map(static fn (string $s) => ['label' => $s, 'count' => (int) ($map[$s] ?? 0)], self::STAGES);
    }

    public static function paginate(int $page, int $perPage = 25, ?string $participation = null, string $search = ''): array
    {
        $pdo = Database::connection();
        $where = ["r.status <> 'cancelled'"];
        $params = [];

        if ($participation && in_array($participation, self::PARTICIPATION, true)) {
            $where[] = 'r.participation = :participation';
            $params['participation'] = $participation;
        }
        if ($search !== '') {
            $where[] = '(r.first_name LIKE :search_first OR r.last_name LIKE :search_last OR r.email LIKE :search_email OR r.reference LIKE :search_reference OR r.country LIKE :search_country)';
            $term = '%' . $search . '%';
            $params['search_first'] = $term;
            $params['search_last'] = $term;
            $params['search_email'] = $term;
            $params['search_reference'] = $term;
            $params['search_country'] = $term;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $count = $pdo->prepare("SELECT COUNT(*) FROM registrations r {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $stmt = $pdo->prepare(
            "SELECT r.id, r.reference, r.participation, r.title, r.first_name, r.last_name, r.email, r.phone,
                    r.kingschat_username, r.country, r.city, r.zone, r.group_name, r.church_name,
                    r.field, r.field_other, r.producer_stage, r.payment_status, r.payment_method,
                    r.issued_by, r.created_at, a.checked_in_at
             FROM registrations r
             LEFT JOIN attendances a ON a.registration_id = r.id {$whereSql}
             ORDER BY r.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'rows'  => $stmt->fetchAll(),
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'page'  => $page,
        ];
    }

    /** Stream every row for CSV export. */
    public static function all(): \Generator
    {
        $stmt = Database::connection()->query(
            'SELECT r.*, a.checked_in_at, a.checked_in_by FROM registrations r LEFT JOIN attendances a ON a.registration_id = r.id ORDER BY r.created_at ASC'
        );
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
}

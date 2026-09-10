<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;

final class AttendanceService
{
    private const PREFIX = 'KPSA1';

    public static function tokenFor(string $reference): string
    {
        $reference = strtoupper(trim($reference));
        return self::PREFIX . ':' . $reference . ':' . self::signature($reference);
    }

    public static function referenceFromToken(string $token): ?string
    {
        $token = trim($token);

        // Accept a copied scanner URL as well as the compact QR payload.
        if (filter_var($token, FILTER_VALIDATE_URL)) {
            $query = [];
            parse_str((string) parse_url($token, PHP_URL_QUERY), $query);
            $token = is_string($query['token'] ?? null) ? $query['token'] : '';
        }

        if (!preg_match('/^' . self::PREFIX . ':(KPS26-[A-HJ-NP-Z2-9]{6}):([a-f0-9]{32})$/i', $token, $matches)) {
            return null;
        }

        $reference = strtoupper($matches[1]);
        return hash_equals(self::signature($reference), strtolower($matches[2])) ? $reference : null;
    }

    public static function checkIn(string $token, string $staffEmail, string $ipAddress): array
    {
        $reference = self::referenceFromToken($token);
        if ($reference === null) {
            return ['ok' => false, 'status' => 'invalid', 'message' => 'This access code is not valid.'];
        }

        $registration = Registration::findByReference($reference);
        if ($registration === null || $registration['status'] === 'cancelled') {
            return ['ok' => false, 'status' => 'invalid', 'message' => 'No active registration matches this code.'];
        }

        if (!in_array($registration['payment_status'], ['paid', 'not_required'], true)) {
            return ['ok' => false, 'status' => 'invalid', 'message' => 'Payment is outstanding. Complete payment before check-in.'];
        }
        if ($registration['status'] !== 'confirmed') {
            return ['ok' => false, 'status' => 'invalid', 'message' => 'This registration is not confirmed. Please contact the organisers.'];
        }

        $attendance = Registration::recordAttendance((int) $registration['id'], $staffEmail, $ipAddress);
        $name = trim($registration['first_name'] . ' ' . $registration['last_name']);

        return [
            'ok'            => true,
            'status'        => $attendance['created'] ? 'checked_in' : 'duplicate',
            'message'       => $attendance['created'] ? 'Access confirmed.' : 'Already checked in.',
            'name'          => $name,
            'reference'     => $reference,
            'participation' => $registration['participation'],
            'checked_in_at' => $attendance['checked_in_at'],
        ];
    }

    private static function signature(string $reference): string
    {
        $key = (string) config('app.key');
        if (strlen($key) < 32) {
            throw new \RuntimeException('APP_KEY must be at least 32 characters to issue attendance credentials.');
        }

        return substr(hash_hmac('sha256', self::PREFIX . '|' . $reference, $key), 0, 32);
    }
}

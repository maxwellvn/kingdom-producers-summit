<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;
use App\Models\Setting;

/**
 * Chase registrations still unpaid a day after they were made. Each one is
 * reminded once, by email and KingsChat.
 */
final class PaymentReminders
{
    public const AFTER_HOURS = 24;

    /** Hours after the reminder before an unpaid place is given back. */
    public const GRACE_HOURS = 72;

    /** How often the sweep may run when triggered by ordinary traffic. */
    private const SWEEP_EVERY = 900;

    /** Give back places still unpaid after the grace period. Returns how many. */
    public static function release(): int
    {
        $released = 0;
        foreach (Registration::dueForRelease(self::GRACE_HOURS) as $registration) {
            if (!Registration::release((int) $registration['id'])) {
                continue; // Paid in the meantime.
            }
            $released++;
            try {
                (new RegistrationMail())->sendPlaceReleased($registration);
            } catch (\Throwable $e) {
                error_log('Place released email failed for ' . $registration['reference'] . ': ' . $e->getMessage());
            }
            (new KingsChatNotifier())->sendPlaceReleased($registration);
        }

        return $released;
    }

    /** Send every due reminder. Returns how many went out. */
    public static function run(): int
    {
        $sent = 0;
        foreach (Registration::dueForPaymentReminder(self::AFTER_HOURS) as $registration) {
            // Mark first, so a failure part-way cannot cause a second reminder.
            Registration::markReminded((int) $registration['id']);
            try {
                (new RegistrationMail())->sendPaymentReminder($registration);
                $sent++;
            } catch (\Throwable $e) {
                error_log('Payment reminder email failed for ' . $registration['reference'] . ': ' . $e->getMessage());
            }
            (new KingsChatNotifier())->sendPaymentReminder($registration);
        }

        return $sent;
    }

    /**
     * Run the sweep from an ordinary request, no more than every few minutes,
     * so reminders go out even where no scheduler has been set up.
     */
    public static function runIfDue(): void
    {
        $last = (int) Setting::get('payment_reminders_swept_at', '0');
        if (time() - $last < self::SWEEP_EVERY) {
            return;
        }
        Setting::set('payment_reminders_swept_at', (string) time());

        // Keep the KingsChat token fresh while we are here, so it never lapses
        // between messages.
        KingsChatClient::refreshIfDue();

        try {
            self::run();
            self::release();
        } catch (\Throwable $e) {
            error_log('Payment reminder sweep failed: ' . $e->getMessage());
        }
    }
}

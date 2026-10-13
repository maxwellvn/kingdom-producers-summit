<?php

declare(strict_types=1);

namespace App\Services;

/**
 * The exhibitor emails: received, the team's alert, approved, declined.
 * Written in the announcement layout, so they look like the rest of the summit's mail.
 * A failed email is logged and never stops the application or the decision.
 */
final class ExhibitorMail
{
    public static function received(array $x): void
    {
        self::send($x, 'We have your application to exhibit, {first_name}',
            "Thank you for applying to exhibit, {first_name}.\n\n"
            . "We have your application for " . $x['business'] . " at the Kingdom Producers Summit, {summit_date}.\n\n"
            . "The team reviews every application and will email you as soon as yours is approved, with what to bring and when to arrive.\n\n"
            . 'Questions in the meantime? Reply to this email or message @lwconsul_uk on KingsChat.');
    }

    public static function approved(array $x): void
    {
        self::send($x, 'You are exhibiting at the summit, {first_name}',
            "YOUR STAND IS CONFIRMED\n\n"
            . "Good news, {first_name}: " . $x['business'] . " is approved to exhibit at the Kingdom Producers Summit.\n\n"
            . "When: {summit_date}\n"
            . "Where: {summit_venue}\n\n"
            . "Plan to arrive early to set up before doors open. We will send setup times and stand details closer to the day.\n\n"
            . "Get directions: {directions_url}\n\n"
            . 'Questions? Reply to this email or message @lwconsul_uk on KingsChat.');
    }

    public static function declined(array $x): void
    {
        self::send($x, 'Your application to exhibit at the summit',
            "Thank you for applying to exhibit, {first_name}.\n\n"
            . "We are not able to offer " . $x['business'] . " a stand at this edition. Space is limited and we could not fit every application.\n\n"
            . "You are still very welcome at the summit itself. Registration is free: {register_url}\n\n"
            . 'Questions? Reply to this email or message @lwconsul_uk on KingsChat.');
    }

    /** A short alert to the team inbox, so a new application is not missed. */
    public static function notifyTeam(array $x): void
    {
        $to = contact_email();
        if ($to === '') {
            return;
        }
        $body = "New application to exhibit at " . (string) config('app.summit.edition') . ".\n\n"
            . 'Business: ' . $x['business'] . "\n"
            . 'Category: ' . $x['category'] . "\n"
            . 'Contact: ' . trim($x['first_name'] . ' ' . $x['last_name']) . ', ' . $x['email'] . "\n\n"
            . 'Review it: ' . site_url() . '/admin/exhibitors';
        self::send(['email' => $to, 'first_name' => 'team', 'last_name' => '', 'reference' => 'EXH-TEAM'], 'New exhibitor application: ' . $x['business'], $body);
    }

    private static function send(array $x, string $subject, string $body): void
    {
        $person = [
            'email'         => (string) $x['email'],
            'first_name'    => (string) $x['first_name'],
            'last_name'     => (string) ($x['last_name'] ?? ''),
            'reference'     => 'EXH-' . ($x['id'] ?? $x['reference'] ?? '0'),
            'participation' => 'exhibitor',
        ];
        try {
            Announcer::deliverOrThrow($person, $subject, $body);
        } catch (\Throwable $e) {
            error_log('Exhibitor email failed (' . $subject . '): ' . $e->getMessage());
        }
    }
}

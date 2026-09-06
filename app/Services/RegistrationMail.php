<?php

declare(strict_types=1);

namespace App\Services;

final class RegistrationMail
{
    /** @param array<string,mixed> $registration */
    public function send(array $registration): void
    {
        $firstName = htmlspecialchars((string) $registration['first_name'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $registration['reference'], ENT_QUOTES, 'UTF-8');
        $path = [
            'onsite' => 'Attending in London',
            'online' => 'Following online',
            'initiative' => 'Kingdom Producers member',
        ][(string) $registration['participation']] ?? 'Registered producer';
        if ($registration['participation'] === 'onsite' && ($registration['payment_status'] ?? '') === 'paid') {
            $amount = number_format((int) $registration['payment_amount'] / 100, 2);
            $path .= " \u{00B7} \u{00A3}{$amount} paid";
        }
        $path = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        $site = rtrim((string) config('app.url'), '/');
        $crest = htmlspecialchars($site . '/assets/img/crest.png', ENT_QUOTES, 'UTF-8');
        $texture = htmlspecialchars($site . '/assets/img/summit-tower-bridge-halftone-v1.jpg', ENT_QUOTES, 'UTF-8');
        $accessToken = AttendanceService::tokenFor((string) $registration['reference']);
        $confirmationUrl = $site . '/register/confirmed?access=' . rawurlencode($accessToken);
        $confirmation = htmlspecialchars($confirmationUrl, ENT_QUOTES, 'UTF-8');

        $subject = 'Registration confirmed — ' . (string) $registration['reference'];
        $html = '<!doctype html><html><body style="margin:0;background:#eae3d2;color:#1b2242;font-family:Arial,Helvetica,sans-serif">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eae3d2"><tr><td align="center" style="padding:32px 16px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#f3eee2;border:1px solid #c9c1af">'
            . '<tr><td style="padding:26px 32px;background:#f3eee2"><img src="' . $crest . '" width="184" alt="The Loveworld Consulate, United Kingdom" style="display:block;width:184px;max-width:100%;height:auto;border:0"></td></tr>'
            . '<tr><td background="' . $texture . '" style="padding:54px 32px;background-color:#1b2242;background-image:linear-gradient(rgba(27,34,66,.84),rgba(27,34,66,.84)),url(\'' . $texture . '\');background-size:cover;color:#f3eee2">'
            . '<p style="margin:0 0 22px;color:#ef6166;font:12px monospace;letter-spacing:2px;text-transform:uppercase">London Edition 2026</p>'
            . '<h1 style="margin:0 0 20px;color:#f3eee2;font:700 52px/0.95 Arial Narrow,Arial,sans-serif;letter-spacing:-1px;text-transform:uppercase">You are registered,<br>' . $firstName . '.</h1>'
            . '<p style="max-width:430px;margin:0;color:#ded8cb;font-size:17px;line-height:1.55">Your place in the Loveworld Kingdom Producers Summit has been recorded.</p></td></tr>'
            . '<tr><td style="padding:32px"><p style="margin:0 0 8px;color:#b4232b;font:12px monospace;letter-spacing:1.5px;text-transform:uppercase">Registration reference</p>'
            . '<p style="margin:0 0 28px;color:#1b2242;font:700 32px Arial Narrow,Arial,sans-serif;letter-spacing:2px">' . $reference . '</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:15px 0;border-top:1px solid #d4ccbb;color:#756f60;font:12px monospace;text-transform:uppercase">Your path</td><td align="right" style="padding:15px 0;border-top:1px solid #d4ccbb;color:#1b2242;font-size:15px">' . $path . '</td></tr></table>'
            . '<p style="margin:26px 0 0;color:#6e6857;font-size:15px;line-height:1.6">Keep this reference safe. Onsite attendees can present the QR access pass shown on the confirmation page when arriving at the attendance desk.</p>'
            . '<p style="margin:28px 0 8px"><a href="' . $confirmation . '" style="display:inline-block;padding:15px 22px;background:#b4232b;color:#f3eee2;text-decoration:none;font-weight:bold;letter-spacing:1px;text-transform:uppercase">View your registration</a></p></td></tr>'
            . '<tr><td style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · London Edition 2026</td></tr>'
            . '</table></td></tr></table></body></html>';

        $text = "You are registered, {$registration['first_name']}.\n\n"
            . "Registration reference: {$registration['reference']}\n"
            . "Your path: " . html_entity_decode($path) . "\n\n"
            . "Keep this reference safe. View your registration: {$confirmationUrl}\n\n"
            . "The Loveworld Consulate, United Kingdom";

        (new Mailer())->send((string) $registration['email'], $subject, $html, $text, [
            'Reply-To' => (string) config('app.mail.reply_to'),
        ]);
    }
}

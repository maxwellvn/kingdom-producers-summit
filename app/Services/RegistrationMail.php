<?php

declare(strict_types=1);

namespace App\Services;

final class RegistrationMail
{
    /** A contribution by Espees or Revolut has been claimed and awaits an organiser's confirmation. */
    public function sendClaimReceived(array $registration): void
    {
        $firstName = htmlspecialchars((string) $registration['first_name'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $registration['reference'], ENT_QUOTES, 'UTF-8');
        $site = site_url();
        $participation = (string) $registration['participation'];
        $paid = payment_phrase($registration);
        $crest = htmlspecialchars($site . '/assets/img/crest.png', ENT_QUOTES, 'UTF-8');
        $texture = htmlspecialchars($site . '/assets/img/summit-tower-bridge-halftone-v1.jpg', ENT_QUOTES, 'UTF-8');

        $subject = 'Thank you — your contribution is being confirmed (' . (string) $registration['reference'] . ')';
        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="color-scheme" content="light only"><meta name="supported-color-schemes" content="light only">'
            . '<style>:root{color-scheme:light only;supported-color-schemes:light only}'
            . '[data-ogsc] .dark-safe-ink{color:#1b2242!important}[data-ogsc] .dark-safe-paper{color:#f3eee2!important}'
            . '</style></head>'
            . '<body style="margin:0;background:#eae3d2;color:#1b2242;font-family:Arial,Helvetica,sans-serif">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eae3d2"><tr><td align="center" style="padding:32px 16px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#f3eee2;border:1px solid #c9c1af">'
            . '<tr><td style="padding:26px 32px;background:#f3eee2"><img src="' . $crest . '" width="184" alt="The Loveworld Consulate, United Kingdom" style="display:block;width:184px;max-width:100%;height:auto;border:0"></td></tr>'
            . '<tr><td bgcolor="#1b2242" background="' . $texture . '" style="padding:54px 32px;background-color:#1b2242;background-image:linear-gradient(rgba(27,34,66,.84),rgba(27,34,66,.84)),url(\'' . $texture . '\');background-size:cover;color:#f3eee2">'
            . '<p style="margin:0 0 22px;color:#ef6166;font:12px monospace;letter-spacing:2px;text-transform:uppercase">' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . ' &middot; ' . htmlspecialchars((string) config('app.summit.date_text'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<h1 style="margin:0 0 20px;color:#f3eee2;font:700 52px/0.95 Arial Narrow,Arial,sans-serif;letter-spacing:-1px;text-transform:uppercase" class="dark-safe-paper">Thank you,<br>' . $firstName . '.</h1>'
            . '<p style="max-width:430px;margin:0;color:#ded8cb;font-size:17px;line-height:1.55">Your ' . htmlspecialchars($paid, ENT_QUOTES, 'UTF-8') . ' contribution to the programme has been logged. We are confirming it now.</p></td></tr>'
            . '<tr><td style="padding:32px"><p style="margin:0 0 8px;color:#b4232b;font:12px monospace;letter-spacing:1.5px;text-transform:uppercase">Registration reference</p>'
            . '<p style="margin:0 0 28px;color:#1b2242;font:700 32px Arial Narrow,Arial,sans-serif;letter-spacing:2px">' . $reference . '</p>'
            . '<p style="margin:0 0 18px;color:#6e6857;font-size:15px;line-height:1.6">We confirm every contribution against our own records, so there is nothing for you to send us. Your place is already confirmed; this changes nothing about it.</p>'
            . '</td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · ' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . '<br><br>'
            . 'If our emails are hard to find, check your spam or promotions folder and mark us as safe.</td></tr>'
            . '</table></td></tr></table></body></html>';

        $text = "Thank you, {$registration['first_name']}.\n\n"
            . "Your {$paid} contribution to the programme has been logged for reference {$registration['reference']}. We are confirming it now.\n\n"
            . "We confirm every contribution against our own records, so there is nothing for you to send us. Your place is already confirmed.\n"
            . "\nThe Loveworld Consulate, United Kingdom";

        (new Mailer())->send((string) $registration['email'], $subject, $html, $text, [
            'Reply-To' => (string) config('app.mail.reply_to'),
        ]);
    }

    /** A contribution has been confirmed. Nothing else changes: the place was already theirs. */
    public function sendSupportThanks(array $registration): void
    {
        $firstName = htmlspecialchars((string) $registration['first_name'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $registration['reference'], ENT_QUOTES, 'UTF-8');
        $site = site_url();
        $paid = htmlspecialchars(payment_phrase($registration), ENT_QUOTES, 'UTF-8');
        $crest = htmlspecialchars($site . '/assets/img/crest.png', ENT_QUOTES, 'UTF-8');
        $texture = htmlspecialchars($site . '/assets/img/summit-tower-bridge-halftone-v1.jpg', ENT_QUOTES, 'UTF-8');

        $subject = 'Thank you for supporting the programme (' . (string) $registration['reference'] . ')';
        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="color-scheme" content="light only"><meta name="supported-color-schemes" content="light only">'
            . '<style>:root{color-scheme:light only;supported-color-schemes:light only}'
            . '[data-ogsc] .dark-safe-ink{color:#1b2242!important}[data-ogsc] .dark-safe-paper{color:#f3eee2!important}'
            . '</style></head>'
            . '<body style="margin:0;background:#eae3d2;color:#1b2242;font-family:Arial,Helvetica,sans-serif">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eae3d2"><tr><td align="center" style="padding:32px 16px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#f3eee2;border:1px solid #c9c1af">'
            . '<tr><td style="padding:26px 32px;background:#f3eee2"><img src="' . $crest . '" width="184" alt="The Loveworld Consulate, United Kingdom" style="display:block;width:184px;max-width:100%;height:auto;border:0"></td></tr>'
            . '<tr><td bgcolor="#1b2242" background="' . $texture . '" style="padding:54px 32px;background-color:#1b2242;background-image:linear-gradient(rgba(27,34,66,.84),rgba(27,34,66,.84)),url(\'' . $texture . '\');background-size:cover;color:#f3eee2">'
            . '<p style="margin:0 0 22px;color:#ef6166;font:12px monospace;letter-spacing:2px;text-transform:uppercase">' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . ' &middot; ' . htmlspecialchars((string) config('app.summit.date_text'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<h1 style="margin:0 0 20px;color:#f3eee2;font:700 52px/0.95 Arial Narrow,Arial,sans-serif;letter-spacing:-1px;text-transform:uppercase" class="dark-safe-paper">Thank you,<br>' . $firstName . '.</h1>'
            . '<p style="max-width:430px;margin:0;color:#ded8cb;font-size:17px;line-height:1.55">Your ' . $paid . ' contribution to the Kingdom Producers programme is confirmed. It goes directly into what the summit and the initiative make possible.</p></td></tr>'
            . '<tr><td style="padding:32px"><p style="margin:0 0 8px;color:#b4232b;font:12px monospace;letter-spacing:1.5px;text-transform:uppercase">Registration reference</p>'
            . '<p style="margin:0 0 20px;color:#1b2242;font:700 32px Arial Narrow,Arial,sans-serif;letter-spacing:2px">' . $reference . '</p>'
            . '<p style="margin:0;color:#6e6857;font-size:15px;line-height:1.6">Your place was already confirmed, and nothing about it changes. Keep your earlier confirmation email; it carries everything you need for the day.</p></td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · ' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table></td></tr></table></body></html>';

        $text = "Thank you, {$registration['first_name']}.\n\n"
            . "Your " . payment_phrase($registration) . " contribution to the Kingdom Producers programme is confirmed.\n\n"
            . "Registration reference: {$registration['reference']}\n"
            . "Your place was already confirmed, and nothing about it changes.\n\n"
            . "The Loveworld Consulate, United Kingdom";

        (new Mailer())->send((string) $registration['email'], $subject, $html, $text, [
            'Reply-To' => (string) config('app.mail.reply_to'),
        ]);
    }

    /** @param array<string,mixed> $registration */
    /** The attendee's QR pass as raw PNG bytes, for embedding in the email itself. */
    private static function qrPng(string $token): ?string
    {
        if (!function_exists('imagecreate')) {
            error_log('QR pass omitted from the email: the gd extension is missing from this PHP build.');
            return null;
        }

        try {
            $old = error_reporting(E_ALL & ~E_DEPRECATED); // vendored phpqrcode predates 8.3 signatures
            require_once BASE_PATH . '/lib/phpqrcode.php';
            ob_start();
            \QRcode::png($token, false, \QR_ECLEVEL_M, 6, 3, false, 0xFFFFFF, 0x000000);
            $png = (string) ob_get_clean();
            error_reporting($old);

            $signature = chr(0x89) . 'PNG';
            if (!str_starts_with($png, $signature)) {
                $pos = strpos($png, $signature);
                $png = $pos === false ? '' : substr($png, $pos);
            }

            return $png === '' ? null : $png;
        } catch (\Throwable $e) {
            error_log('QR pass could not be rendered for the email: ' . $e->getMessage());
            return null;
        }
    }

    public function send(array $registration): void
    {
        $firstName = htmlspecialchars((string) $registration['first_name'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $registration['reference'], ENT_QUOTES, 'UTF-8');
        $path = [
            'onsite' => 'Attending onsite',
            'online' => 'Attending online',
            'initiative' => 'Joined the Kingdom Producers initiative',
        ][(string) $registration['participation']] ?? 'Registered producer';
        if (($registration['payment_status'] ?? '') === 'paid') {
            $path .= " \u{00B7} " . payment_phrase($registration) . ' contributed';
        }
        $path = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        $site = site_url();
        $crest = htmlspecialchars($site . '/assets/img/crest.png', ENT_QUOTES, 'UTF-8');
        $texture = htmlspecialchars($site . '/assets/img/summit-tower-bridge-halftone-v1.jpg', ENT_QUOTES, 'UTF-8');
        $accessToken = AttendanceService::tokenFor((string) $registration['reference']);
        $confirmationUrl = $site . '/register/confirmed?access=' . rawurlencode($accessToken);
        $confirmation = htmlspecialchars($confirmationUrl, ENT_QUOTES, 'UTF-8');

        // Only onsite delegates are checked in at the door, so only they get a pass.
        $onsite = (string) $registration['participation'] === 'onsite';
        $qrPng = $onsite ? self::qrPng($accessToken) : null;
        $qrCid = 'access-pass';
        $qrUrl = $qrPng !== null
            ? 'cid:' . $qrCid
            : htmlspecialchars($site . '/access/qr?token=' . rawurlencode($accessToken), ENT_QUOTES, 'UTF-8');

        $supportBlock = '';
        if (is_paid_path((string) $registration['participation']) && ($registration['payment_status'] ?? '') === 'not_required') {
            $suggested = espees_price(price_pence((string) $registration['participation']));
            $supportUrl = htmlspecialchars($site . '/register/method?resume=' . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])), ENT_QUOTES, 'UTF-8');
            $supportBlock = '<p style="margin:26px 0 0;color:#1b2242;font-size:15px;line-height:1.6">If you would like to support the programme, a contribution of ' . $suggested . ' is suggested, though any amount and none at all are equally welcome.</p>'
                . '<p style="margin:14px 0 0"><a href="' . $supportUrl . '" style="display:inline-block;padding:12px 18px;border:2px solid #1b2242;color:#1b2242;text-decoration:none;font-weight:bold;letter-spacing:1px;text-transform:uppercase;font-size:13px">Support the programme</a></p>';
        }

        $passBlock = $onsite
            ? '<div style="margin:24px 0 6px;text-align:center"><img src="' . $qrUrl . '" width="180" height="180" alt="Your QR access pass" style="display:block;margin:0 auto;border:1px solid #d4ccbb;background:#ffffff;padding:8px"><p style="margin:10px 0 0;color:#756f60;font:11px monospace;letter-spacing:1px;text-transform:uppercase">Show this QR at the attendance desk</p></div>'
              . '<p style="margin:26px 0 0;color:#6e6857;font-size:15px;line-height:1.6">Keep this reference safe and present the QR access pass at the attendance desk when you arrive.</p>'
            : ((string) $registration['participation'] === 'online'
                ? '<p style="margin:26px 0 0;color:#6e6857;font-size:15px;line-height:1.6">Your live viewing link is sent to this email address before the programme begins. There is no pass to bring, and nothing further to do until then — just keep this reference safe.</p>'
                : '<p style="margin:26px 0 0;color:#6e6857;font-size:15px;line-height:1.6">Keep this reference safe for any correspondence with us about the initiative.</p>');

        $subject = 'Registration confirmed — ' . (string) $registration['reference'];
        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="color-scheme" content="light only"><meta name="supported-color-schemes" content="light only">'
            . '<style>:root{color-scheme:light only;supported-color-schemes:light only}'
            . '[data-ogsc] .dark-safe-ink{color:#1b2242!important}[data-ogsc] .dark-safe-paper{color:#f3eee2!important}'
            . '</style></head>'
            . '<body style="margin:0;background:#eae3d2;color:#1b2242;font-family:Arial,Helvetica,sans-serif">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eae3d2"><tr><td align="center" style="padding:32px 16px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#f3eee2;border:1px solid #c9c1af">'
            . '<tr><td style="padding:26px 32px;background:#f3eee2"><img src="' . $crest . '" width="184" alt="The Loveworld Consulate, United Kingdom" style="display:block;width:184px;max-width:100%;height:auto;border:0"></td></tr>'
            . '<tr><td bgcolor="#1b2242" background="' . $texture . '" style="padding:54px 32px;background-color:#1b2242;background-image:linear-gradient(rgba(27,34,66,.84),rgba(27,34,66,.84)),url(\'' . $texture . '\');background-size:cover;color:#f3eee2">'
            . '<p style="margin:0 0 22px;color:#ef6166;font:12px monospace;letter-spacing:2px;text-transform:uppercase">' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . ' &middot; ' . htmlspecialchars((string) config('app.summit.date_text'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<h1 style="margin:0 0 20px;color:#f3eee2;font:700 52px/0.95 Arial Narrow,Arial,sans-serif;letter-spacing:-1px;text-transform:uppercase" class="dark-safe-paper">You are registered,<br>' . $firstName . '.</h1>'
            . '<p style="max-width:430px;margin:0;color:#ded8cb;font-size:17px;line-height:1.55">Your place in the Loveworld Kingdom Producers Summit has been recorded.</p></td></tr>'
            . '<tr><td style="padding:32px"><p style="margin:0 0 10px;color:#b4232b;font:12px monospace;letter-spacing:1.5px;text-transform:uppercase">Registration reference — keep this</p>'
            . '<p style="margin:0 0 28px;padding:16px 20px;background:#1b2242;color:#ffffff;font:700 40px/1.1 Arial Black,Arial Narrow,Arial,sans-serif;letter-spacing:3px;text-align:center">' . $reference . '</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td style="padding:15px 0;border-top:1px solid #d4ccbb;color:#756f60;font:12px monospace;text-transform:uppercase">Your path</td><td align="right" style="padding:15px 0;border-top:1px solid #d4ccbb;color:#1b2242;font-size:15px">' . $path . '</td></tr></table>'
            . $passBlock
            . $supportBlock
            . '<p style="margin:28px 0 8px"><a href="' . $confirmation . '" style="display:inline-block;padding:15px 22px;background:#b4232b;color:#f3eee2;text-decoration:none;font-weight:bold;letter-spacing:1px;text-transform:uppercase">View your registration</a></p></td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · ' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . '<br><br>'
            . 'If our emails are hard to find, check your spam or promotions folder and mark us as safe.</td></tr>'
            . '</table></td></tr></table></body></html>';

        $text = "You are registered, {$registration['first_name']}.\n\n"
            . "Registration reference: {$registration['reference']}\n"
            . "Your path: " . html_entity_decode($path) . "\n\n"
            . ($onsite
                ? "Present your QR access pass at the attendance desk when you arrive.\n"
                : ((string) $registration['participation'] === 'online'
                    ? "Your live viewing link is sent to this email address before the programme begins.\n"
                    : ''))
            . "Keep this reference safe. View your registration: {$confirmationUrl}\n\n"
            . (is_paid_path((string) $registration['participation']) && ($registration['payment_status'] ?? '') === 'not_required'
                ? "If you would like to support the programme, a contribution of " . espees_price(price_pence((string) $registration['participation'])) . " is suggested: "
                  . $site . '/register/method?resume=' . rawurlencode(PaymentService::resumeToken((string) $registration['reference'])) . "\n\n"
                : '')
            . "The Loveworld Consulate, United Kingdom";

        (new Mailer())->send((string) $registration['email'], $subject, $html, $text, [
            'Reply-To' => (string) config('app.mail.reply_to'),
        ], $qrPng !== null ? [$qrCid => ['data' => $qrPng, 'type' => 'image/png']] : []);
    }
}

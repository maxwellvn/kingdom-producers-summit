<?php

declare(strict_types=1);

namespace App\Services;

final class RegistrationMail
{
    /** Offline payment claim logged: registration confirmed, QR pass follows once payment is verified. */
    public function sendClaimReceived(array $registration): void
    {
        $firstName = htmlspecialchars((string) $registration['first_name'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $registration['reference'], ENT_QUOTES, 'UTF-8');
        $site = site_url();
        $participation = (string) $registration['participation'];
        $paid = payment_phrase($registration);
        $crest = htmlspecialchars($site . '/assets/img/crest.png', ENT_QUOTES, 'UTF-8');
        $texture = htmlspecialchars($site . '/assets/img/summit-tower-bridge-halftone-v1.jpg', ENT_QUOTES, 'UTF-8');

        $subject = 'Payment received — awaiting confirmation (' . (string) $registration['reference'] . ')';
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
            . '<h1 style="margin:0 0 20px;color:#f3eee2;font:700 52px/0.95 Arial Narrow,Arial,sans-serif;letter-spacing:-1px;text-transform:uppercase" class="dark-safe-paper">Payment received,<br>' . $firstName . '.</h1>'
            . '<p style="max-width:430px;margin:0;color:#ded8cb;font-size:17px;line-height:1.55">Your registration is confirmed and your ' . htmlspecialchars($paid, ENT_QUOTES, 'UTF-8') . ' payment has been logged. We are verifying it now.</p></td></tr>'
            . '<tr><td style="padding:32px"><p style="margin:0 0 8px;color:#b4232b;font:12px monospace;letter-spacing:1.5px;text-transform:uppercase">Registration reference</p>'
            . '<p style="margin:0 0 28px;color:#1b2242;font:700 32px Arial Narrow,Arial,sans-serif;letter-spacing:2px">' . $reference . '</p>'
            . '<p style="margin:0 0 18px;color:#6e6857;font-size:15px;line-height:1.6">We verify every payment against our own records, so there is nothing for you to send us. Your QR access pass is emailed to you the moment your payment is confirmed — keep this reference safe in the meantime.</p>'
            . '</td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · ' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . '<br><br>'
            . 'If our emails are hard to find, check your spam or promotions folder and mark us as safe.</td></tr>'
            . '</table></td></tr></table></body></html>';

        $text = "Payment received, {$registration['first_name']}.\n\n"
            . "Your registration is confirmed and your {$paid} payment has been logged for reference {$registration['reference']}.\n\n"
            . "We verify every payment against our own records, so there is nothing for you to send us.\n"
            . "Your QR access pass is emailed to you the moment your payment is confirmed.\n"
            . "\nThe Loveworld Consulate, United Kingdom";

        (new Mailer())->send((string) $registration['email'], $subject, $html, $text, [
            'Reply-To' => (string) config('app.mail.reply_to'),
        ]);
    }

    /** Onsite acknowledgement: registration recorded, payment still to complete. No access pass yet. */
    public function sendAcknowledgement(array $registration): void
    {
        $firstName = htmlspecialchars((string) $registration['first_name'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $registration['reference'], ENT_QUOTES, 'UTF-8');
        $site = site_url();
        $participation = (string) $registration['participation'];
        $amount = number_format(price_pence($participation) / 100, 2);
        $standard = espees_price(standard_price_pence($participation));
        $payUrl = $site . '/register/pay?ref=' . rawurlencode((string) $registration['reference']);
        $pay = htmlspecialchars($payUrl, ENT_QUOTES, 'UTF-8');
        $crest = htmlspecialchars($site . '/assets/img/crest.png', ENT_QUOTES, 'UTF-8');
        $texture = htmlspecialchars($site . '/assets/img/summit-tower-bridge-halftone-v1.jpg', ENT_QUOTES, 'UTF-8');

        $subject = 'You are on the list — complete your place payment (' . (string) $registration['reference'] . ')';
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
            . '<h1 style="margin:0 0 20px;color:#f3eee2;font:700 52px/0.95 Arial Narrow,Arial,sans-serif;letter-spacing:-1px;text-transform:uppercase" class="dark-safe-paper">You are on the list,<br>' . $firstName . '.</h1>'
            . '<p style="max-width:430px;margin:0;color:#ded8cb;font-size:17px;line-height:1.55">Your place is held. The full price is ' . $standard . '; the inaugural edition price leaves ' . $amount . ' Espees to pay.</p></td></tr>'
            . '<tr><td style="padding:32px"><p style="margin:0 0 8px;color:#b4232b;font:12px monospace;letter-spacing:1.5px;text-transform:uppercase">Registration reference</p>'
            . '<p style="margin:0 0 28px;color:#1b2242;font:700 32px Arial Narrow,Arial,sans-serif;letter-spacing:2px">' . $reference . '</p>'
            . '<p style="margin:28px 0 8px"><a href="' . $pay . '" style="display:inline-block;padding:15px 22px;background:#b4232b;color:#f3eee2;text-decoration:none;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Complete payment</a></p>'
            . '<p style="margin:22px 0 0;color:#6e6857;font-size:15px;line-height:1.6">Your QR access pass is issued by email as soon as your payment is confirmed.</p></td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · ' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . '<br><br>'
            . 'If our emails are hard to find, check your spam or promotions folder and mark us as safe.</td></tr>'
            . '</table></td></tr></table></body></html>';

        $text = "You are on the list, {$registration['first_name']}.\n\n"
            . "Your place is held. The full price is {$standard}; the inaugural edition price leaves {$amount} Espees to pay.\n\n"
            . "Registration reference: {$registration['reference']}\n"
            . "Complete payment: {$payUrl}\n\n"
            . "Your QR access pass is issued by email as soon as your payment is confirmed.\n\n"
            . "The Loveworld Consulate, United Kingdom";

        (new Mailer())->send((string) $registration['email'], $subject, $html, $text, [
            'Reply-To' => (string) config('app.mail.reply_to'),
        ]);
    }
    /**
     * A day on and still unpaid: the place is not held until it is paid for.
     * Sent once per registration.
     */
    public function sendPaymentReminder(array $registration): void
    {
        $firstName = htmlspecialchars((string) $registration['first_name'], ENT_QUOTES, 'UTF-8');
        $reference = htmlspecialchars((string) $registration['reference'], ENT_QUOTES, 'UTF-8');
        $site = site_url();
        $participation = (string) $registration['participation'];
        $amount = espees_price(price_pence($participation));
        $pathLabel = $participation === 'onsite' ? 'onsite place' : 'online place';
        $payUrl = $site . '/register/pay?resume=' . rawurlencode(PaymentService::resumeToken((string) $registration['reference']));
        $pay = htmlspecialchars($payUrl, ENT_QUOTES, 'UTF-8');
        $crest = htmlspecialchars($site . '/assets/img/crest.png', ENT_QUOTES, 'UTF-8');
        $texture = htmlspecialchars($site . '/assets/img/summit-tower-bridge-halftone-v1.jpg', ENT_QUOTES, 'UTF-8');
        $scarce = $participation === 'onsite'
            ? 'Onsite places are limited and go to whoever pays first, so an unpaid registration does not keep one back for you.'
            : 'Online places are limited too, and it is payment that secures one.';

        $subject = 'Your ' . $pathLabel . ' is not yet secured (' . (string) $registration['reference'] . ')';
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
            . '<h1 style="margin:0 0 20px;color:#f3eee2;font:700 52px/0.95 Arial Narrow,Arial,sans-serif;letter-spacing:-1px;text-transform:uppercase" class="dark-safe-paper">Your place is<br>not yet secured, ' . $firstName . '.</h1>'
            . '<p style="max-width:430px;margin:0;color:#ded8cb;font-size:17px;line-height:1.55">You registered a day ago and the ' . $amount . ' for your ' . $pathLabel . ' has not reached us. Registration alone does not hold a place; payment does.</p></td></tr>'
            . '<tr><td style="padding:32px"><p style="margin:0 0 18px;color:#1b2242;font-size:16px;line-height:1.6">' . $scarce . ' Once the places are taken, registration closes and unpaid registrations will be released.</p>'
            . '<p style="margin:0 0 8px;color:#b4232b;font:12px monospace;letter-spacing:1.5px;text-transform:uppercase">Registration reference</p>'
            . '<p style="margin:0 0 20px;color:#1b2242;font:700 32px Arial Narrow,Arial,sans-serif;letter-spacing:2px">' . $reference . '</p>'
            . '<p style="margin:0 0 8px"><a href="' . $pay . '" style="display:inline-block;padding:15px 22px;background:#b4232b;color:#f3eee2;text-decoration:none;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Secure my place now</a></p>'
            . '<p style="margin:22px 0 0;color:#6e6857;font-size:15px;line-height:1.6">If you have already paid, thank you; we are confirming payments as they arrive and you can ignore this message. If you no longer wish to attend, there is nothing you need to do.</p></td></tr>'
            . '<tr><td bgcolor="#1b2242" style="padding:22px 32px;background:#1b2242;color:#aaaebe;font:11px/1.6 monospace;letter-spacing:1px;text-transform:uppercase">The Loveworld Consulate, United Kingdom<br>Kingdom Producers Summit · ' . htmlspecialchars((string) config('app.summit.edition'), ENT_QUOTES, 'UTF-8') . '<br><br>'
            . 'If our emails are hard to find, check your spam or promotions folder and mark us as safe.</td></tr>'
            . '</table></td></tr></table></body></html>';

        $text = "Your place is not yet secured, {$registration['first_name']}.\n\n"
            . "You registered a day ago and the {$amount} for your {$pathLabel} has not reached us. Registration alone does not hold a place; payment does.\n\n"
            . strip_tags($scarce) . " Once the places are taken, registration closes and unpaid registrations will be released.\n\n"
            . "Registration reference: {$registration['reference']}\n"
            . "Secure your place: {$payUrl}\n\n"
            . "If you have already paid, thank you; you can ignore this message.\n\n"
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
            $path .= " \u{00B7} " . payment_phrase($registration) . ' paid';
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
            . "The Loveworld Consulate, United Kingdom";

        (new Mailer())->send((string) $registration['email'], $subject, $html, $text, [
            'Reply-To' => (string) config('app.mail.reply_to'),
        ], $qrPng !== null ? [$qrCid => ['data' => $qrPng, 'type' => 'image/png']] : []);
    }
}

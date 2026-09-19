<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\ConsentController;
use App\Controllers\HomeController;
use App\Controllers\PaymentController;
use App\Controllers\SponsorController;
use App\Controllers\PresenceController;
use App\Controllers\RegistrationController;
use App\Controllers\WatchController;
use App\Core\Router;
use App\Middleware\RequireAdmin;
use App\Middleware\VerifyCsrf;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/privacy', [HomeController::class, 'privacy']);
$router->get('/share', [HomeController::class, 'share']);

$router->get('/register', [RegistrationController::class, 'create']);
$router->post('/register', [RegistrationController::class, 'store'], [VerifyCsrf::class]);
$router->get('/register/confirmed', [RegistrationController::class, 'confirmed']);
$router->get('/register/calendar.ics', [RegistrationController::class, 'calendar']);
$router->post('/register/resend', [RegistrationController::class, 'resend'], [VerifyCsrf::class]);

// The protected stream.
$router->get('/watch', [WatchController::class, 'show']);
$router->post('/watch', [WatchController::class, 'enter'], [VerifyCsrf::class]);
$router->post('/watch/leave', [WatchController::class, 'leave'], [VerifyCsrf::class]);
$router->get('/watch/source', [WatchController::class, 'source']);
$router->post('/watch/beat', [WatchController::class, 'beat']);
$router->get('/watch/comments', [WatchController::class, 'comments']);
$router->post('/watch/comments', [WatchController::class, 'postComment'], [VerifyCsrf::class]);
$router->post('/watch/prompt', [WatchController::class, 'answerPrompt'], [VerifyCsrf::class]);
$router->get('/watch/hls', [WatchController::class, 'hls']);
$router->get('/access/qr', [RegistrationController::class, 'qr']);
$router->get('/register/pay', [PaymentController::class, 'payForm']);
$router->post('/register/pay', [PaymentController::class, 'payResume'], [VerifyCsrf::class]);
$router->get('/register/method', [PaymentController::class, 'methodPage']);
$router->get('/register/instructions', [PaymentController::class, 'instructions']);
$router->get('/register/claim', [PaymentController::class, 'claimForm']);
$router->post('/register/claim', [PaymentController::class, 'claim'], [VerifyCsrf::class]);
$router->get('/register/awaiting', [PaymentController::class, 'awaiting']);
$router->get('/register/stripe', [PaymentController::class, 'stripeStart']);
$router->get('/register/stripe/return', [PaymentController::class, 'stripeReturn']);
// Stripe signs this itself, so it carries no CSRF token.
$router->get('/sponsor', [SponsorController::class, 'show']);
$router->post('/sponsor', [SponsorController::class, 'start'], [VerifyCsrf::class]);
$router->get('/sponsor/send', [SponsorController::class, 'send']);
$router->post('/sponsor/send', [SponsorController::class, 'claim'], [VerifyCsrf::class]);
$router->get('/sponsor/stripe/return', [SponsorController::class, 'stripeReturn']);
$router->get('/sponsor/thanks', [SponsorController::class, 'thanks']);
$router->post('/admin/sponsorships/confirm', [AdminController::class, 'confirmSponsorship'], [VerifyCsrf::class, RequireAdmin::class]);

$router->post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);

// Cookie consent log: anonymous audit row only, no session/CSRF.
$router->post('/api/consent', [ConsentController::class, 'store']);

// Presence heartbeat: no personal data, so no CSRF ceremony.
$router->post('/api/presence', [PresenceController::class, 'beat']);
$router->post('/api/presence/leave', [PresenceController::class, 'leave']);
$router->post('/api/analytics-choice', [PresenceController::class, 'choice']);

$router->get('/admin/login', [AdminController::class, 'loginForm']);
$router->post('/admin/login', [AdminController::class, 'login'], [VerifyCsrf::class]);
$router->post('/admin/logout', [AdminController::class, 'logout'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin', [AdminController::class, 'dashboard'], [RequireAdmin::class]);
$router->get('/admin/registrations', [AdminController::class, 'registrations'], [RequireAdmin::class]);
$router->get('/admin/scanner', [AdminController::class, 'scanner'], [RequireAdmin::class]);
$router->get('/admin/payments', [AdminController::class, 'paymentSettings'], [RequireAdmin::class]);
$router->post('/admin/payments', [AdminController::class, 'savePaymentSettings'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/registrations/resend', [AdminController::class, 'resend'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/registrations/resend-all', [AdminController::class, 'resendAll'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/registrations/bulk-status', [AdminController::class, 'bulkStatus'], [RequireAdmin::class]);
$router->post('/admin/registrations/confirm-payment', [AdminController::class, 'confirmPayment'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/registrations/delete', [AdminController::class, 'deleteRegistration'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/admins', [AdminController::class, 'admins'], [RequireAdmin::class]);
$router->post('/admin/admins', [AdminController::class, 'addAdmin'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/admins/delete', [AdminController::class, 'deleteAdmin'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/check-in', [AdminController::class, 'checkIn'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/stream', [AdminController::class, 'stream'], [RequireAdmin::class]);
$router->post('/admin/stream', [AdminController::class, 'saveStream'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/stream/state', [AdminController::class, 'setStreamState'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/notifications', [AdminController::class, 'notifications'], [RequireAdmin::class]);
$router->post('/admin/notifications', [AdminController::class, 'queueNotification'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/notifications/cancel', [AdminController::class, 'cancelNotification'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/stream/load-test', [AdminController::class, 'startLoadTest'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/stream/load-test/stop', [AdminController::class, 'stopLoadTest'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/stream/load-test', [AdminController::class, 'loadTestStatus'], [RequireAdmin::class]);
$router->get('/admin/stream/log', [AdminController::class, 'streamLog'], [RequireAdmin::class]);
$router->post('/admin/stream/log/clear', [AdminController::class, 'clearStreamLog'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/prompts', [AdminController::class, 'createPrompt'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/prompts/close', [AdminController::class, 'closePrompt'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/prompts/results', [AdminController::class, 'promptResults'], [RequireAdmin::class]);
$router->post('/admin/event-day', [AdminController::class, 'eventDay'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/comments', [AdminController::class, 'saveComments'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/comments/delete', [AdminController::class, 'deleteComments'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/analytics', [AdminController::class, 'analytics'], [RequireAdmin::class]);
$router->get('/admin/analytics/live', [AdminController::class, 'analyticsLive'], [RequireAdmin::class]);
$router->get('/admin/issue', [AdminController::class, 'issueForm'], [RequireAdmin::class]);
$router->post('/admin/issue', [AdminController::class, 'issue'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/kingschat', [AdminController::class, 'kingschat'], [RequireAdmin::class]);
// KingsChat returns here as a cross-site POST, so no session or CSRF token
// arrives with it. The tokens are parked and claimed by the signed-in admin
// on the same-site redirect that follows.
$router->get('/admin/kingschat/callback', [AdminController::class, 'kingschatReturn']);
$router->post('/admin/kingschat/callback', [AdminController::class, 'kingschatReturn']);
$router->post('/admin/kingschat/disconnect', [AdminController::class, 'kingschatDisconnect'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/kingschat/test', [AdminController::class, 'kingschatTest'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/export.csv', [AdminController::class, 'exportCsv'], [RequireAdmin::class]);

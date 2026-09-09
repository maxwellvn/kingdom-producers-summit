<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\ConsentController;
use App\Controllers\HomeController;
use App\Controllers\PaymentController;
use App\Controllers\RegistrationController;
use App\Core\Router;
use App\Middleware\RequireAdmin;
use App\Middleware\VerifyCsrf;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);

$router->get('/register', [RegistrationController::class, 'create']);
$router->post('/register', [RegistrationController::class, 'store'], [VerifyCsrf::class]);
$router->get('/register/confirmed', [RegistrationController::class, 'confirmed']);
$router->get('/access/qr', [RegistrationController::class, 'qr']);
$router->get('/register/paid', [PaymentController::class, 'paid']);
$router->get('/register/pay', [PaymentController::class, 'payForm']);
$router->post('/register/pay', [PaymentController::class, 'payResume'], [VerifyCsrf::class]);
$router->get('/register/method', [PaymentController::class, 'methodPage']);
$router->get('/register/instructions', [PaymentController::class, 'instructions']);
$router->get('/register/claim', [PaymentController::class, 'claimForm']);
$router->post('/register/claim', [PaymentController::class, 'claim'], [VerifyCsrf::class]);
$router->get('/register/awaiting', [PaymentController::class, 'awaiting']);
$router->post('/register/pay/checkout', [PaymentController::class, 'checkout'], [VerifyCsrf::class]);

// PayPal webhook: verified against PayPal's signature API, no session/CSRF.
$router->post('/paypal/webhook', [PaymentController::class, 'webhook']);

// Cookie consent log: anonymous audit row only, no session/CSRF.
$router->post('/api/consent', [ConsentController::class, 'store']);

$router->get('/admin/login', [AdminController::class, 'loginForm']);
$router->post('/admin/login', [AdminController::class, 'login'], [VerifyCsrf::class]);
$router->post('/admin/logout', [AdminController::class, 'logout'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin', [AdminController::class, 'dashboard'], [RequireAdmin::class]);
$router->get('/admin/registrations', [AdminController::class, 'registrations'], [RequireAdmin::class]);
$router->get('/admin/scanner', [AdminController::class, 'scanner'], [RequireAdmin::class]);
$router->get('/admin/payments', [AdminController::class, 'paymentSettings'], [RequireAdmin::class]);
$router->post('/admin/payments', [AdminController::class, 'savePaymentSettings'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/registrations/confirm-payment', [AdminController::class, 'confirmPayment'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/registrations/delete', [AdminController::class, 'deleteRegistration'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/admins', [AdminController::class, 'admins'], [RequireAdmin::class]);
$router->post('/admin/admins', [AdminController::class, 'addAdmin'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/admins/delete', [AdminController::class, 'deleteAdmin'], [VerifyCsrf::class, RequireAdmin::class]);
$router->post('/admin/check-in', [AdminController::class, 'checkIn'], [VerifyCsrf::class, RequireAdmin::class]);
$router->get('/admin/export.csv', [AdminController::class, 'exportCsv'], [RequireAdmin::class]);

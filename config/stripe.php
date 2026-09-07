<?php

declare(strict_types=1);

return [
    'enabled'        => filter_var(env('STRIPE_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
    'secret'         => env('STRIPE_LIVE_SECRET_KEY', ''),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),

    'currency'      => 'gbp',
    'price_pence'   => 5000,  // place price
];

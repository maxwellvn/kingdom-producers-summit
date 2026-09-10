<?php

declare(strict_types=1);

return [
    'enabled'    => filter_var(env('PAYPAL_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
    'client_id'  => env('PAYPAL_CLIENT_ID', ''),
    'secret'     => env('PAYPAL_SECRET', ''),
    'mode'       => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
    'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),

    'currency'    => 'gbp',
    'standard_price_pence' => 10000,
    'price_pence' => 5000, // 100 Espees less the 50 Espees attendance discount
];

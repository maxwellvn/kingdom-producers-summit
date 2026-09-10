<?php

declare(strict_types=1);

return [
    'enabled'    => filter_var(env('PAYPAL_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
    'client_id'  => env('PAYPAL_CLIENT_ID', ''),
    'secret'     => env('PAYPAL_SECRET', ''),
    'mode'       => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
    'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),

    'currency'    => 'gbp',

    // Per-path pricing. "standard" is the full price, "due" is what the
    // inaugural discount leaves to pay. Paths absent here cost nothing.
    'prices' => [
        'onsite' => ['standard' => 10000, 'due' => 5000],
        'online' => ['standard' => 5000,  'due' => 2000],
    ],

    // Onsite figures, kept for anything still reading them directly.
    'standard_price_pence' => 10000,
    'price_pence' => 5000,
];

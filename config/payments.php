<?php

declare(strict_types=1);

return [
    // Ways to give, shown to anyone who chooses to support the programme.
    // Edit here and redeploy; a method only appears publicly when its details are filled.

    'espees' => [
        'enabled' => true,
        'code'    => 'SALWC', // editable in the admin panel; this is the fallback
        'note'    => '',
    ],

    'revolut' => [
        'enabled' => true,
        'url'     => 'https://checkout.revolut.com/pay/be6a58e7-4418-4854-b243-f1845e33f86d',
        // Open-amount link used on /sponsor; editable in the admin panel.
        'sponsor_url' => 'https://checkout.revolut.com/pay/26b11f6d-bc0d-457a-a363-2c70e7c91023',
        'note'    => '',
    ],

    // Card payments through Stripe Checkout. Keys live in .env, never here.
    'stripe' => [
        'enabled'        => true,
        'secret'         => (string) env('STRIPE_SECRET_KEY', ''),
        'publishable'    => (string) env('STRIPE_PUBLISHABLE_KEY', ''),
        'webhook_secret' => (string) env('STRIPE_WEBHOOK_SECRET', ''),
        // Prices are held in pence, so the charge is made in pounds sterling.
        'currency'       => 'gbp',
    ],

    // Contact details for enquiries. Delegates are never asked to send proof; organisers verify payments themselves.
    'proof' => [
        'kingschat' => 'lwconsul_uk',
        'email'     => 'unitedkingdom@loveworldconsulate.org',
    ],
];

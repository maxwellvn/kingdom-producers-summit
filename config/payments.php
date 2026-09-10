<?php

declare(strict_types=1);

return [
    // Payment methods shown to paying registrants, in display order: Espees → Revolut.
    // Edit here and redeploy; a method only appears publicly when its details are filled.

    'espees' => [
        'enabled' => true,
        'code'    => 'SALWC', // editable in the admin panel; this is the fallback
        'note'    => '',
    ],

    'revolut' => [
        'enabled' => true,
        'url'     => 'https://checkout.revolut.com/pay/be6a58e7-4418-4854-b243-f1845e33f86d',
        'note'    => '',
    ],

    // Contact details for enquiries. Delegates are never asked to send proof; organisers verify payments themselves.
    'proof' => [
        'kingschat' => 'lwconsul_uk',
        'email'     => 'unitedkingdom@loveworldconsulate.org',
    ],
];

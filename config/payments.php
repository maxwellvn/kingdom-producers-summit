<?php

declare(strict_types=1);

return [
    // Payment methods shown to paying registrants, in display order: Espees → Revolut.
    // Edit here and redeploy; a method only appears publicly when its details are filled.

    'espees' => [
        'enabled' => true,
        'code'    => '', // ← your Espees code goes here; Espees stays hidden until it is set
        'note'    => '',
    ],

    'revolut' => [
        'enabled' => true,
        'url'     => 'https://checkout.revolut.com/pay/be6a58e7-4418-4854-b243-f1845e33f86d',
        'note'    => '',
    ],

    'proof' => [
        'kingschat' => 'lwconsul_uk',
        'email'     => 'unitedkingdom@loveworldconsulate.org',
    ],
];

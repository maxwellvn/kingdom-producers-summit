<?php

declare(strict_types=1);

return [
    // Payment methods shown to onsite registrants, in order: Espees → PayPal → Bank.
    // Edit here and redeploy; a method only appears publicly when its details are filled.

    'espees' => [
        'enabled' => true,
        'code'    => '', // ← your Espees code goes here; Espees stays hidden until it is set
        'note'    => '',
    ],

    'paypal_enabled' => true,

    'bank' => [
        'enabled'          => true,
        'account_name'     => 'LOVEWORLD CONSULATE LIMITED',
        'account_address'  => '128 City Road, EC1V 2NX, London, United Kingdom',
        'account_number'   => '90500026',
        'sort_code'        => '23-01-63',
        'iban'             => 'GB61 REVO 2301 6390 5000 26',
        'bic'              => 'REVOGB21',
        'intermediary_bic' => 'CHASGB2L',
        'note'             => '',
    ],

    'proof' => [
        'kingschat' => '', // leave empty to hide the KingsChat line
        'email'     => '', // falls back to MAIL_REPLY_TO when empty
    ],
];

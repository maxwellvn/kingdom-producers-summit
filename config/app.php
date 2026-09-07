<?php

declare(strict_types=1);

return [
    'name'   => env('APP_NAME', 'The Loveworld Kingdom Producers Summit'),
    'env'    => env('APP_ENV', 'production'),
    'debug'  => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url'    => rtrim((string) env('APP_URL', ''), '/'),
    'key'    => env('APP_KEY', ''),

    'admin' => [
        'email'         => env('ADMIN_EMAIL', ''),
        'password_hash' => env('ADMIN_PASSWORD_HASH', ''),
    ],

    'mail' => [
        'mailer'    => env('MAIL_MAILER', 'smtp'),
        'host'      => env('MAIL_HOST', ''),
        'port'      => (int) env('MAIL_PORT', '465'),
        'encryption'=> env('MAIL_ENCRYPTION', 'ssl'),
        'username'  => env('MAIL_USERNAME', ''),
        'password'  => env('MAIL_PASSWORD', ''),
        'from'      => env('MAIL_FROM', 'lkps@loveworldconsulate.org'),
        'from_name' => env('MAIL_FROM_NAME', 'Loveworld Kingdom Producers Summit'),
        'reply_to'  => env('MAIL_REPLY_TO', 'lkps@loveworldconsulate.org'),
    ],

    'summit' => [
        'short'     => 'The Producers Summit',
        'edition'   => 'London Edition 2026',
        'city'      => 'London, United Kingdom',
        'date_text' => 'Date to be announced',
        'organiser' => 'Loveworld Consulate UK',
        'office'    => 'The Loveworld Consulate, United Kingdom',
        'motto'     => 'Exceptionalism. Expansionism. Perfectionism.',
        'onsite_capacity' => 100,
        'targets'   => ['100 Producers', '1,000 UK', '10,000 Global'],
        'stages'    => ['Emerge', 'Build', 'Establish', 'Multiply'],
        'partners'  => ['Ministry of Commerce', 'The Office of the Exchequer', 'Loveworld Consulate UK'],
    ],
];

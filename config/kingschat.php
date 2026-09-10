<?php

declare(strict_types=1);

return [
    // Sending is skipped entirely when this is off.
    'enabled' => filter_var(env('KINGSCHAT_ENABLED', 'true'), FILTER_VALIDATE_BOOL),

    // The registered application. Authorisation is granted once by an
    // organiser signing in as the sending account; tokens are then stored.
    'client_id' => env('KINGSCHAT_CLIENT_ID', 'com.kingschat'),
    'scopes'    => ['conference_calls'],

    // The account the messages come from, used only for display.
    'sender'    => env('KINGSCHAT_SENDER', 'lwconsul_uk'),

    'endpoints' => [
        'authorize' => 'https://accounts.kingsch.at/',
        'token'     => 'https://connect.kingsch.at/oauth2/token',
        'contacts'  => 'https://connect.kingsch.at/api/contacts',
        'profile'   => 'https://connect.kingsch.at/api/profile',
        'message'   => 'https://connect.kingsch.at/api/users/%s/new_message',
    ],
];

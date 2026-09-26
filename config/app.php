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
        'from'      => env('MAIL_FROM', 'unitedkingdom@loveworldconsulate.org'),
        'from_name' => env('MAIL_FROM_NAME', 'Loveworld Kingdom Producers Summit'),
        'reply_to'  => env('MAIL_REPLY_TO', 'unitedkingdom@loveworldconsulate.org'),
    ],

    // One halftone print per slot, per city. Filenames live in public/assets/img.
    'artwork' => [
        'london' => [
            'summit' => 'summit-tower-bridge-halftone-v1.jpg', 'poster' => 'poster-st-pauls-halftone-v1.jpg',
            'pathways' => 'pathways-westminster-halftone-v1.jpg', 'process' => 'process-battersea-halftone-v1.jpg',
            'cta' => 'cta-wembley-halftone-v1.jpg', 'portal' => 'portal-british-library-halftone-v1.jpg',
            'footer' => 'footer-royal-albert-hall-halftone-v1.jpg',
        ],
        'manchester' => [
            'summit' => 'summit-town-hall-halftone-v1.jpg', 'poster' => 'poster-central-library-halftone-v1.jpg',
            'pathways' => 'pathways-castlefield-halftone-v1.jpg', 'process' => 'process-ancoats-mills-halftone-v1.jpg',
            'cta' => 'cta-salford-quays-halftone-v1.jpg', 'portal' => 'portal-john-rylands-halftone-v1.jpg',
            'footer' => 'footer-manchester-skyline-halftone-v1.jpg',
        ],
    ],

    'summit' => [
        'short'     => 'The Producers Summit',
        'edition'   => 'London Edition 2026',
        'place'     => 'Rainham',
        // Which set of halftone prints dresses the site; chosen per edition in admin → Current edition.
        'artwork'   => 'london',
        'city'      => 'Rainham, Essex, United Kingdom',
        'venue'     => [
            'name'     => 'Thamesview Business Centre',
            'unit'     => 'Unit C2',
            'street'   => 'Barlow Way',
            'town'     => 'Rainham',
            'postcode' => 'RM13 8BT',
            'region'   => 'London',
            // What the maps apps are given, so everyone lands on the same pin.
            'query'    => 'Unit C2, Thamesview Business Centre, Barlow Way, Rainham RM13 8BT',
        ],
        // ISO start/end drive the countdown and the calendar file; the text lines are for reading.
        'starts_at' => '2026-09-19T12:00:00+01:00',
        'ends_at'   => '2026-09-19T18:00:00+01:00',
        'date_text' => 'Saturday 19th September 2026, 12 noon',
        'date_day'  => 'Saturday 19th September 2026',
        'time'      => '12 noon',
        // How to get there; each line shows on the home page only when it has text. Editable in admin → Current edition.
        'travel'    => [
            'train' => 'Rainham station is on the c2c line from London Fenchurch Street, about 25 minutes, with trains also from Limehouse, West Ham and Barking. The venue is a short walk or a few minutes by taxi from the station.',
            'bus'   => 'Routes 103, 165, 287 and 372 serve Rainham. Check the stop nearest Barlow Way on the day, as routes change.',
            'car'   => 'Off the A1306 and close to the A13, with the M25 at junction 30 a short drive away. Parking is available at the business centre.',
        ],
        'organiser' => 'Loveworld Consulate UK',
        'office'    => 'The Loveworld Consulate, United Kingdom',
        'motto'     => 'Exceptionalism. Expansionism. Perfectionism.',
        'targets'   => ['1,000 Producers', '90 Days', 'One Community'],
        'stages'    => ['Nothing yet', 'Getting started', 'Growing', 'Scaling'],
        'partners'  => ['Ministry of Commerce', 'The Office of the Exchequer', 'Loveworld Consulate UK'],
    ],
];

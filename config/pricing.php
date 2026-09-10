<?php

declare(strict_types=1);

return [
    // What each participation path costs. "standard" is the full price and
    // "due" is what the inaugural edition leaves to pay, both in pence.
    // A path that is absent costs nothing and skips the payment flow.
    'prices' => [
        'onsite' => ['standard' => 10000, 'due' => 5000],
        'online' => ['standard' => 5000,  'due' => 2000],
    ],
];

<?php

declare(strict_types=1);

return [
    // Attending is free. These are the suggested contributions offered after
    // registering, in pence. A path that is absent is never asked to give.
    'prices' => [
        'onsite' => ['due' => 5000],
        'online' => ['due' => 2000],
    ],
];

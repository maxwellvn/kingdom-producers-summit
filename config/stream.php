<?php

declare(strict_types=1);

return [
    // Everything here is edited from Admin > Stream and stored in settings.
    // These are only the fallbacks for a fresh deployment.
    'enabled' => false,
    'url'     => '',
    'title'   => 'Kingdom Producers Summit — live',
    'note'    => 'The stream begins at 12 noon. Keep this page open.',

    // Serve an HLS stream through the site so the real address is never
    // handed to the browser. Only applies to .m3u8 links.
    'proxy'   => true,

    // Which registrations may watch.
    'allowed' => ['onsite', 'online'],
];

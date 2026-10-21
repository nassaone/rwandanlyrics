<?php

return [
    // Set REGISTRATION_ENABLED=false in .env to close public sign-up.
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', true),

    // Admin forms are entered in this time zone and stored as UTC.
    'admin_timezone' => env('ADMIN_TIMEZONE', 'Africa/Kigali'),
];

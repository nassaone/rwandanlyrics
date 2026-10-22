<?php

return [
    // Set REGISTRATION_ENABLED=false in .env to close public sign-up.
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', true),

    // Admin forms are entered in this time zone and stored as UTC.
    'admin_timezone' => env('ADMIN_TIMEZONE', 'Africa/Kigali'),

    // Filesystem disk for uploaded images (use an S3-compatible disk later).
    'media_disk' => env('MEDIA_DISK', 'public'),

    'contact' => [
        'copyright' => env('CONTACT_COPYRIGHT_EMAIL', 'copyright@yourdomain.com'),
        'corrections' => env('CONTACT_CORRECTIONS_EMAIL', 'corrections@yourdomain.com'),
        'privacy' => env('CONTACT_PRIVACY_EMAIL', 'privacy@yourdomain.com'),
    ],
];

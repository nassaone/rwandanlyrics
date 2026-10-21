<?php

return [
    // Set REGISTRATION_ENABLED=false in .env to close public sign-up.
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', true),
];

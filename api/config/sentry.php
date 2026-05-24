<?php

return [

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Performance monitoring sample rate (0.0 to 1.0)
    // 0.1 = 10% of requests traced
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Profile requests (0.0 to 1.0)
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // Send default PII (user IPs, etc.) — disabled for RGPD
    'send_default_pii' => false,

    // Environment tag
    'environment' => env('APP_ENV', 'production'),

    // Release tag — update on each deploy
    'release' => env('SENTRY_RELEASE', '1.0.0'),

    // Only report in production/staging
    'in_app_exclude' => [
        'vendor',
    ],

];

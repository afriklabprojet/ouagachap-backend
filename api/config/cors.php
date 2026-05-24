<?php

// Origines CORS résolues une seule fois au boot.
// Les apps Flutter mobiles n'envoient pas d'en-tête Origin :
// cette config protège uniquement les clients web/admin.
$corsOrigins = array_filter(
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
    fn (string $o) => $o !== '',
);
if (empty($corsOrigins) && env('APP_ENV') !== 'production') {
    $corsOrigins = ['*'];
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Security hardened CORS configuration for OUAGA CHAP API.
    | Toujours définir CORS_ALLOWED_ORIGINS explicitement en production.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values($corsOrigins),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Socket-Id',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400,

    'supports_credentials' => false,

];

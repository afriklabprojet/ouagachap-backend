<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JEKO Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration du paiement mobile money via JEKO
    | https://developer.jeko.africa/
    |
    */

    // API Credentials
    'api_key' => env('JEKO_API_KEY', ''),
    'api_key_id' => env('JEKO_API_KEY_ID', ''),
    'store_id' => env('JEKO_STORE_ID', ''),
    
    // Webhook Secret (pour vérifier les signatures)
    'webhook_secret' => env('JEKO_WEBHOOK_SECRET', ''),

    // API URLs
    'base_url' => env('JEKO_BASE_URL', 'https://api.jeko.africa'),
    'payment_endpoint' => '/partner_api/payment_requests',

    // Callback URLs (pour l'app mobile)
    'app_scheme' => env('APP_SCHEME', 'ouagachap'),
    'success_path' => '/payment/success',
    'error_path' => '/payment/error',
    
    // Web Callback URLs (fallback)
    'web_success_url' => env('JEKO_WEB_SUCCESS_URL', null),
    'web_error_url' => env('JEKO_WEB_ERROR_URL', null),

    // Default currency
    'currency' => 'XOF',

    // Méthodes de paiement supportées
    'payment_methods' => [
        'wave' => [
            'code' => 'wave',
            'name' => 'Wave',
            'icon' => '🌊',
            'color' => '#1DC3E8',
            'countries' => ['BF', 'CI', 'SN', 'ML'],
        ],
        'orange' => [
            'code' => 'orange',
            'name' => 'Orange Money',
            'icon' => '🟠',
            'color' => '#FF6600',
            'countries' => ['BF', 'CI', 'SN', 'ML', 'NE', 'GN'],
        ],
        'mtn' => [
            'code' => 'mtn',
            'name' => 'MTN Mobile Money',
            'icon' => '🟡',
            'color' => '#FFCC00',
            'countries' => ['CI', 'GH', 'CM', 'BJ'],
        ],
        'moov' => [
            'code' => 'moov',
            'name' => 'Moov Money',
            'icon' => '🔵',
            'color' => '#0066CC',
            'countries' => ['BF', 'CI', 'BJ', 'TG', 'NE'],
        ],
        'djamo' => [
            'code' => 'djamo',
            'name' => 'Djamo',
            'icon' => '💳',
            'color' => '#6C5CE7',
            'countries' => ['CI'],
        ],
    ],

    // Minimum/Maximum amounts (en FCFA)
    'min_amount' => 100, // 100 FCFA minimum
    'max_amount' => 1000000, // 1,000,000 FCFA maximum

    // Timeout pour la vérification de statut (en secondes)
    'status_check_timeout' => 300, // 5 minutes

    // Mode sandbox (pour les tests)
    'sandbox' => env('JEKO_SANDBOX', true),
];

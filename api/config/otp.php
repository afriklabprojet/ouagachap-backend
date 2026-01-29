<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Driver
    |--------------------------------------------------------------------------
    |
    | Définit le driver à utiliser pour l'envoi des codes OTP.
    | Valeurs supportées: "firebase", "sms", "log"
    |
    | - "firebase": Utilise Firebase Phone Authentication (recommandé en production)
    | - "sms": Utilise l'envoi SMS direct via Twilio/autre provider
    | - "log": Mode développement - log le code dans storage/logs
    |
    */
    'driver' => env('AUTH_OTP_DRIVER', 'firebase'),

    /*
    |--------------------------------------------------------------------------
    | Fallback SMS
    |--------------------------------------------------------------------------
    |
    | Si activé et que Firebase échoue, le système utilisera l'envoi SMS direct.
    |
    */
    'fallback_sms' => env('AUTH_OTP_FALLBACK_SMS', false),

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | En mode démo, le code 123456 est toujours accepté.
    | NE PAS activer en production !
    |
    */
    'demo_mode' => env('AUTH_OTP_DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Demo Code
    |--------------------------------------------------------------------------
    |
    | Le code OTP accepté en mode démo.
    |
    */
    'demo_code' => env('AUTH_OTP_DEMO_CODE', '123456'),

    /*
    |--------------------------------------------------------------------------
    | OTP Expiration
    |--------------------------------------------------------------------------
    |
    | Durée de validité du code OTP en minutes (pour le mode SMS).
    | Firebase gère son propre timeout.
    |
    */
    'expiration_minutes' => env('AUTH_OTP_EXPIRATION', 5),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Nombre maximum de tentatives d'envoi OTP par numéro par heure.
    |
    */
    'max_attempts_per_hour' => env('AUTH_OTP_MAX_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Allowed Countries
    |--------------------------------------------------------------------------
    |
    | Liste des codes pays autorisés pour l'authentification.
    | Vide = tous les pays autorisés.
    |
    */
    'allowed_countries' => [
        '226', // Burkina Faso
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Project
    |--------------------------------------------------------------------------
    |
    | Configuration Firebase spécifique à l'authentification.
    |
    */
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT', 'ouaga-chap'),
        'credentials_path' => env('FIREBASE_CREDENTIALS', storage_path('firebase-credentials.json')),
    ],
];

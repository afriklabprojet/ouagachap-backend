<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sappay Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Intégration paiement mobile money via Sappay (OTP flow)
    | Base Public API  : https://api.prod.sappay.net/api/public
    | Base Checkout API: https://api.prod.sappay.net/api/checkout
    |
    */

    'client_id'     => env('SAPPAY_CLIENT_ID', ''),
    'client_secret' => env('SAPPAY_CLIENT_SECRET', ''),
    'username'      => env('SAPPAY_USERNAME', ''),
    'password'      => env('SAPPAY_PASSWORD', ''),

    // URLs de base
    'public_url'   => env('SAPPAY_PUBLIC_URL',   'https://api.prod.sappay.net/api/public'),
    'checkout_url' => env('SAPPAY_CHECKOUT_URL',  'https://api.prod.sappay.net/api/checkout'),
    'disburse_url' => env('SAPPAY_DISBURSE_URL',  'https://api.prod.sappay.net/api/public'),

    // Cache du token d'accès (secondes, légèrement sous les 3600 de l'API)
    'token_ttl' => 3500,

    // Mode sandbox (désactivé en production)
    'sandbox' => env('SAPPAY_SANDBOX', false),

    // Whitelist IP pour les callbacks Sappay — séparées par des virgules (CIDR supporté).
    // Laisser vide uniquement hors production. En production, toute valeur vide → 403.
    // Renseigner dès que Sappay publie ses IPs sortantes officielles.
    'webhook_allowed_ips' => env('SAPPAY_WEBHOOK_ALLOWED_IPS', ''),

    // Secret HMAC pour valider la signature du webhook Sappay (header X-Sappay-Signature).
    // Vide = bloqué en production. À renseigner dès que Sappay communique le secret.
    // Format de signature attendu : sha256=<hex> (à ajuster selon leur doc).
    'webhook_secret' => env('SAPPAY_WEBHOOK_SECRET', ''),

    // Pays du client (1 = Burkina Faso par défaut)
    'default_country' => 1,

    // Montants limites (en FCFA)
    'min_amount' => 100,
    'max_amount' => 1000000,

    // Moyens de paiement supportés avec leurs payment_processor_id Sappay
    'payment_methods' => [
        'orange_money' => [
            'name'                 => 'Orange Money',
            'payment_processor_id' => '11688813752134336',
            'requires_get_otp'     => false,
            'icon'                 => '🟠',
            'color'                => '#FF6600',
        ],
        'telecel_money' => [
            'name'                 => 'Telecel Money',
            'payment_processor_id' => '11744695746597207',
            'requires_get_otp'     => false,
            'icon'                 => '🔴',
            'color'                => '#CC0000',
        ],
        'moov_money' => [
            'name'                 => 'Moov Money',
            'payment_processor_id' => '11688813838374580',
            'requires_get_otp'     => true,
            'icon'                 => '🔵',
            'color'                => '#0066CC',
        ],
        'coris_money' => [
            'name'                 => 'Coris Money',
            'payment_processor_id' => '11702302492453862',
            'requires_get_otp'     => true,
            'icon'                 => '🟢',
            'color'                => '#00AA44',
        ],
    ],
];

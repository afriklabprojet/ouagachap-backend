<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "twilio", "log" (for development)
    |
    */
    'default' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers Configuration
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],

        'log' => [
            // Log SMS to storage/logs for development
            'channel' => 'sms',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'length' => 6,
        'expires_minutes' => 5,
        'max_attempts' => 3,
        'resend_delay_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Templates (French - Burkina Faso)
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'otp' => "OUAGA CHAP: Votre code de verification est :code. Valide :minutes min.",
        'order_created' => "OUAGA CHAP: Commande :tracking creee. Nous recherchons un coursier.",
        'order_assigned' => "OUAGA CHAP: :courier_name va recuperer votre colis.",
        'order_delivered' => "OUAGA CHAP: Votre colis a ete livre. Merci!",
    ],
];

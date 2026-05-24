<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    |
    | Définit le provider SMS à utiliser.
    | Valeurs supportées: "brevo", "infobip", "twilio", "log"
    |
    | - "brevo"   : Envoi via l'API Brevo Transactional SMS (recommandé)
    | - "infobip" : Envoi réel via l'API Infobip (recommandé pour l'Afrique)
    | - "twilio"  : Envoi réel via l'API Twilio
    | - "log"     : Simule le SMS dans les logs (développement / CI)
    |
    */
    'default' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Indicatif pays par défaut (E.164)
    |--------------------------------------------------------------------------
    */
    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '+226'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | Nombre max de SMS par numéro par heure.
    */
    'rate_limit' => (int) env('SMS_RATE_LIMIT', 30),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'log_messages' => env('SMS_LOG_MESSAGES', false),
    'log_channel'  => env('SMS_LOG_CHANNEL', 'sms'),
];

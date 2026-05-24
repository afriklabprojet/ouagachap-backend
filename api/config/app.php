<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Configuration
    |--------------------------------------------------------------------------
    */

    'commission_rate' => (float) env('COMMISSION_RATE', 0.15), // 15% par défaut
    'currency' => env('APP_CURRENCY', 'XOF'),
    'default_base_price' => (int) env('DEFAULT_BASE_PRICE', 500),
    'default_price_per_km' => (int) env('DEFAULT_PRICE_PER_KM', 200),
    'min_withdrawal_amount' => (int) env('MIN_WITHDRAWAL_AMOUNT', 500),
    'min_recharge_amount' => (int) env('MIN_RECHARGE_AMOUNT', 100),
    'max_recharge_amount' => (int) env('MAX_RECHARGE_AMOUNT', 500000),
    'recharge_amounts' => array_map('intval', explode(',', env('RECHARGE_AMOUNTS', '500,1000,2000,5000,10000,20000'))),
    'min_order_amount' => (int) env('MIN_ORDER_AMOUNT', 500),
    'max_order_amount' => (int) env('MAX_ORDER_AMOUNT', 100000),
    'support_phone' => env('SUPPORT_PHONE', '+22670000000'),
    'support_email' => env('SUPPORT_EMAIL', 'support@ouagachap.com'),
    'version' => env('APP_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Store Links
    |--------------------------------------------------------------------------
    */

    'store_links' => [
        'client' => [
            'google_play' => env('STORE_LINK_CLIENT_GOOGLE_PLAY', '#'),
            'app_store' => env('STORE_LINK_CLIENT_APP_STORE', '#'),
        ],
        'coursier' => [
            'google_play' => env('STORE_LINK_COURSIER_GOOGLE_PLAY', '#'),
            'app_store' => env('STORE_LINK_COURSIER_APP_STORE', '#'),
        ],
    ],

];

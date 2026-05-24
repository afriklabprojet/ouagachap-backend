<?php

/**
 * Configuration MySQL pour production - OUAGA CHAP
 * 
 * Instructions de déploiement :
 * 1. Créer la base de données MySQL
 * 2. Configurer .env avec les variables ci-dessous
 * 3. Exécuter : php artisan migrate --force
 * 4. Exécuter : php artisan db:seed --class=ProductionSeeder (optionnel)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Variables d'environnement à configurer
    |--------------------------------------------------------------------------
    */
    
    'env_variables' => [
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_DATABASE' => 'ouaga_chap',
        'DB_USERNAME' => 'ouaga_chap_user',
        'DB_PASSWORD' => 'your_secure_password',
        'DB_CHARSET' => 'utf8mb4',
        'DB_COLLATION' => 'utf8mb4_unicode_ci',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration MySQL optimisée
    |--------------------------------------------------------------------------
    */
    
    'mysql_config' => [
        'driver' => 'mysql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'ouaga_chap'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => 'InnoDB',
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            PDO::ATTR_PERSISTENT => true,
        ]) : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Index recommandés pour les performances
    |--------------------------------------------------------------------------
    */
    
    'recommended_indexes' => [
        'orders' => [
            'idx_orders_status' => 'status',
            'idx_orders_client_id' => 'client_id',
            'idx_orders_courier_id' => 'courier_id',
            'idx_orders_zone' => 'pickup_zone_id, delivery_zone_id',
            'idx_orders_created_at' => 'created_at',
            'idx_orders_delivered_at' => 'delivered_at',
        ],
        'users' => [
            'idx_users_role' => 'role',
            'idx_users_phone' => 'phone',
            'idx_users_is_active' => 'is_active',
        ],
        'payments' => [
            'idx_payments_status' => 'status',
            'idx_payments_order_id' => 'order_id',
        ],
        'courier_profiles' => [
            'idx_courier_is_available' => 'is_available',
            'idx_courier_current_location' => 'current_latitude, current_longitude',
        ],
        'withdrawals' => [
            'idx_withdrawals_status' => 'status',
            'idx_withdrawals_user_id' => 'user_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de pool de connexions
    |--------------------------------------------------------------------------
    */
    
    'connection_pool' => [
        'min_connections' => 5,
        'max_connections' => 20,
        'idle_timeout' => 60,
    ],
];

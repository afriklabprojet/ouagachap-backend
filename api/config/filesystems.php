<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    | En développement/staging : local
    | En production avec CDN   : s3 (Cloudflare R2 ou Hetzner Object Storage)
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => true,
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        // Cloudflare R2 ou Hetzner Object Storage (S3-compatible).
        // Activer en production : FILESYSTEM_DISK=s3
        // Les URLs publiques sont servies via CDN_URL (ex: https://cdn.ouagachap.pro).
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('CDN_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
            'visibility'              => 'public',
        ],

        // Disque temporaire pour les exports (non exposé publiquement)
        'exports' => [
            'driver' => 'local',
            'root'   => storage_path('app/exports'),
            'throw'  => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    | php artisan storage:link crée le lien public/storage → storage/app/public
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

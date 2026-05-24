<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\WhatFailureGroupHandler;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'sentry' => [
            'driver' => 'sentry',
            'level'  => env('LOG_LEVEL', 'error'),
            'bubble' => true,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'security' => [
            'driver' => 'single',
            'path'   => storage_path('logs/security.log'),
            'level'  => 'warning',
        ],

        // Better Stack (Logtail) — centralisation des logs en production
        // Activer en ajoutant 'logtail' à LOG_STACK dans .env.production
        // Obtenir le source token sur https://logs.betterstack.com → Sources → New source
        // Prérequis : composer require logtail/monolog-logtail
        'logtail' => [
            'driver' => 'monolog',
            'level'  => env('LOGTAIL_LEVEL', 'debug'),
            'handler' => \App\Logging\BetterstackHandler::class,
            'handler_with' => [
                'sourceToken' => env('LOGTAIL_SOURCE_TOKEN', ''),
                'level'       => env('LOGTAIL_LEVEL', 'debug'),
            ],
        ],

    ],

];

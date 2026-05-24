<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Suppress deprecated warnings in PHP 8.5+ so they don't pollute HTTP responses
if (PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION >= 5) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Stub for missing dev-only packages not installed in production vendor
if (! class_exists('Knuckles\\Scribe\\ScribeServiceProvider')) {
    require_once __DIR__.'/../app/Providers/ScribeStub.php';
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());

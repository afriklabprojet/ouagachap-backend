<?php

/**
 * Stub provider to prevent "Class not found" errors when Scribe (dev-only)
 * is listed in packages.php but not installed in vendor on production.
 */

namespace Knuckles\Scribe;

use Illuminate\Support\ServiceProvider;

class ScribeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}
}

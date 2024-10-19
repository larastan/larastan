<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Laravel\Lumen\Application as LumenApplication;
use Larastan\Larastan\ApplicationResolver;
use Orchestra\Testbench\Concerns\CreatesApplication;

if (! defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

if (file_exists($applicationPath = getcwd().'/bootstrap/app.php')) { // Applications and Local Dev
    $app = require $applicationPath;
} elseif (file_exists($applicationPath = dirname(__DIR__, 3).'/bootstrap/app.php')) { // Relative path from default vendor dir
    $app = require $applicationPath;
} elseif (trait_exists(CreatesApplication::class)) { // Packages
    $app = ApplicationResolver::resolve();
}

if (isset($app)) {
    if ($app instanceof Application) {
        $app->make(Kernel::class)->bootstrap();
    } elseif ($app instanceof LumenApplication) {
        $app->boot();
    }

    if (! defined('LARAVEL_VERSION')) {
        define('LARAVEL_VERSION', $app->version());
    }
}

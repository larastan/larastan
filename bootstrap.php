<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Larastan\Larastan\ApplicationResolver;
use Larastan\Larastan\Support\BootstrapErrorHandler;
use Laravel\Lumen\Application as LumenApplication;
use Orchestra\Testbench\Concerns\CreatesApplication;

if (! defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

try {
    $applicationPath = getcwd() . '/bootstrap/app.php';
    if (file_exists($applicationPath)) { // Applications and Local Dev
        $app = require $applicationPath;
    } else {
        $applicationPath = dirname(__DIR__, 3) . '/bootstrap/app.php';
        if (file_exists($applicationPath)) { // Relative path from default vendor dir
            $app = require $applicationPath;
        } elseif (trait_exists(CreatesApplication::class)) { // Packages
            $app = ApplicationResolver::resolve();
        }
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
} catch (Throwable $throwable) {
    $argv      = $_SERVER['argv'] ?? [];
    $decorated = null;

    if (in_array('--no-ansi', $argv, true)) {
        $decorated = false;
    } elseif (in_array('--ansi', $argv, true)) {
        $decorated = true;
    }

    (new BootstrapErrorHandler(decorated: $decorated))->handle($throwable);
    exit(1);
}

<?php

declare(strict_types=1);

use NunoMaduro\Larastan\ApplicationResolver;

define('LARAVEL_START', microtime(true));

if (file_exists($applicationPath = getcwd().'/bootstrap/app.php')) { // Applications and Local Dev
    $app = require $applicationPath;
} elseif (file_exists($applicationPath = dirname(__DIR__, 3).'/bootstrap/app.php')) { // Relative path from default Laravel vendor dir
    $app = require $applicationPath;
} elseif (trait_exists(Orchestra\Testbench\Concerns\CreatesApplication::class)) { // Packages
    $app = ApplicationResolver::resolve();
} else {
    throw new \Exception('Could not find Laravel bootstrap file nor Testbench is installed. Install orchestra/testbench if analyzing a package.');
}

if ($app instanceof \Illuminate\Contracts\Foundation\Application) {
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} elseif ($app instanceof \Laravel\Lumen\Application) {
    $app->boot();
}

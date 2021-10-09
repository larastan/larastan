<?php

declare(strict_types=1);

use NunoMaduro\Larastan\ApplicationResolver;

define('LARAVEL_START', microtime(true));

$app = null;
if (file_exists($applicationPath = getcwd().'/bootstrap/app.php')) { // Applications and Local Dev
    $app = require $applicationPath;
} elseif (trait_exists(Orchestra\Testbench\Concerns\CreatesApplication::class)) { // Packages
    $app = ApplicationResolver::resolve();
}

if ($app instanceof \Illuminate\Contracts\Foundation\Application) {
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} elseif ($app instanceof \Laravel\Lumen\Application) {
    $app->boot();
}

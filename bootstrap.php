<?php

declare(strict_types=1);

/*
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

use NunoMaduro\Larastan\ApplicationResolver;

define('LARAVEL_START', microtime(true));

if (file_exists($applicationPath = __DIR__.'/../../../bootstrap/app.php')) { // Applications
    $app = require $applicationPath;
} elseif (file_exists($applicationPath = getcwd().'/../../../../bootstrap/app.php')) { // Local Dev
    $app = require $applicationPath;
} else { // Packages
    $app = ApplicationResolver::resolve();
}

if ($app instanceof \Illuminate\Contracts\Foundation\Application) {
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
}

$app->make('config')->set('larastan.mixins', require __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'mixins.php');
$app->make('config')->set('larastan.statics', require __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'statics.php');

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

define('LARAVEL_START', microtime(true));

$app = require __DIR__.'/../../../bootstrap/app.php';

if ($app instanceof \Illuminate\Contracts\Foundation\Application) {
    $app->make(\Illuminate\Contracts\Console\Kernel::class)
        ->bootstrap();
}

$app->make('config')
    ->set('larastan.mixins', require __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'mixins.php');
$app->make('config')
    ->set('larastan.statics', require __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'statics.php');

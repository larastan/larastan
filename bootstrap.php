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

$app->make(\Illuminate\Contracts\Console\Kernel::class)
    ->bootstrap();

<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan;

use Illuminate\Support\ServiceProvider;
use NunoMaduro\Larastan\Console\CodeAnalyseCommand;

/**
 * @internal
 */
final class LarastanServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(CodeAnalyseCommand::class);
        }
    }
}

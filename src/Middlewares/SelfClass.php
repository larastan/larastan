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

namespace NunoMaduro\Larastan\Middlewares;

use Closure;
use NunoMaduro\Larastan\Passable;

/**
 * @internal
 */
final class SelfClass
{
    /**
     * @param \NunoMaduro\Larastan\Passable $passable
     * @param \Closure $next
     *
     * @return void
     */
    public function handle(Passable $passable, Closure $next): void
    {
        $className = $passable->getClassReflection()
            ->getName();

        if (! $passable->searchOn($className)) {
            $next($passable);
        }
    }
}

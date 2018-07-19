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

namespace NunoMaduro\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Support\Facades\Facade;
use NunoMaduro\Larastan\Methods\Passable;

/**
 * @internal
 */
final class Facades
{
    /**
     * @param \NunoMaduro\Larastan\Methods\Passable $passable
     * @param \Closure $next
     *
     * @return void
     */
    public function handle(Passable $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->isSubclassOf(Facade::class)) {
            $facadeClass = $classReflection->getName();

            if ($concrete = $facadeClass::getFacadeRoot()) {
                $found = $passable->sendToPipeline(get_class($concrete), true);
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

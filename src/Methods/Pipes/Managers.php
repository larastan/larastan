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
use InvalidArgumentException;
use Illuminate\Support\Manager;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class Managers implements PipeContract
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->isSubclassOf(Manager::class)) {
            $driver = null;

            $concrete = $this->resolve(
                $classReflection->getName()
            );

            try {
                $driver = $concrete->driver();
            } catch (InvalidArgumentException $exception) {
                // ..
            }

            if ($driver !== null) {
                $found = $passable->sendToPipeline(get_class($driver));
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

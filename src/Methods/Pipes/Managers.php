<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Support\Manager;
use InvalidArgumentException;
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

        if ($classReflection->isSubclassOf(Manager::class) && ! $classReflection->isAbstract()) {
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
                $class = get_class($driver);

                if ($class) {
                    $found = $passable->sendToPipeline($class);
                }
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

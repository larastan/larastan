<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use Larastan\Larastan\Contracts\Methods\Pipes\PipeContract;

/** @internal */
final class Managers implements PipeContract
{
    use Concerns\HasContainer;

    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->is(Manager::class) && ! $classReflection->isAbstract()) {
            $driver = null;

            $concrete = $this->resolve(
                $classReflection->getName(),
            );

            try {
                $driver = $concrete->driver();
            } catch (InvalidArgumentException) {
                // ..
            }

            if ($driver !== null) {
                $class = $driver::class;

                if ($class) {
                    $found = $passable->sendToPipeline($class);
                }
            }
        }

        if ($found) {
            return;
        }

        $next($passable);
    }
}

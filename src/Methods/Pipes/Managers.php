<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use Larastan\Larastan\Contracts\Methods\Pipes\PipeContract;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;

/** @internal */
final class Managers implements PipeContract
{
    use Concerns\HasContainer;

    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->isSubclassOf(Manager::class) && ! $classReflection->isAbstract()) {
            $concrete = $this->resolve(
                $classReflection->getName(),
            );

            $class = null;

            $createDefaultDriverMethod = 'create' . Str::studly($concrete->getDefaultDriver()) . 'Driver';
            if ($classReflection->hasNativeMethod($createDefaultDriverMethod)) {
                $createDefaultDriverMethod = $classReflection->getNativeMethod($createDefaultDriverMethod);
                $methodReturnType = ParametersAcceptorSelector::selectSingle($createDefaultDriverMethod->getVariants())->getReturnType();
                $class            = $methodReturnType->getObjectClassNames()[0] ?? null;
            }

            if (! $class) {
                try {
                    $driver = $concrete->driver();

                    if ($driver !== null) {
                        $class = $driver::class;
                    }
                } catch (InvalidArgumentException) {
                    // ..
                }
            }

            if ($class) {
                $found = $passable->sendToPipeline($class);
            }
        }

        if ($found) {
            return;
        }

        $next($passable);
    }
}

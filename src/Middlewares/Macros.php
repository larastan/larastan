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
use NunoMaduro\Larastan\Macro;
use NunoMaduro\Larastan\Passable;
use Illuminate\Support\Traits\Macroable;
use NunoMaduro\Larastan\Concerns\HasContainer;

/**
 * @internal
 */
final class Macros
{
    use HasContainer;

    /**
     * @param \NunoMaduro\Larastan\Passable $passable
     * @param \Closure $next
     *
     * @return void
     */
    public function handle(Passable $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $className = null;
        $found = false;

        if ($classReflection->isInterface()) {
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $className = get_class($concrete);
                $haveTrait = $passable->getBroker()
                    ->getClass($className)
                    ->hasTraitUse(Macroable::class);
            } else {
                $className = null;
                $haveTrait = false;
            }
        } else {
            /** @var \Illuminate\Support\Traits\Macroable $macroable */
            $className = $classReflection->getName();
            $haveTrait = $classReflection->hasTraitUse(Macroable::class);
        }

        if ($haveTrait) {
            $refObject = new \ReflectionClass($className);
            $refProperty = $refObject->getProperty('macros');
            $refProperty->setAccessible(true);

            if ($found = $className::hasMacro($passable->getMethodName())) {
                $reflectionFunction = new \ReflectionFunction($refProperty->getValue()[$passable->getMethodName()]);
                $passable->setMethodReflection(
                    $passable->getMethodReflectionFactory()
                        ->create(
                            $classReflection,
                            null,
                            new Macro(
                                $classReflection->getName(), $passable->getMethodName(), $reflectionFunction
                            ),
                            $reflectionFunction->getParameters(),
                            null,
                            null,
                            false,
                            false,
                            false
                        )
                );
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

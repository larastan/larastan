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

use Carbon\Traits\Macro as CarbonMacro;
use Closure;
use Illuminate\Support\Traits\Macroable;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;
use NunoMaduro\Larastan\Methods\Macro;
use PHPStan\Reflection\ClassReflection;

/**
 * @internal
 */
final class Macros implements PipeContract
{
    use Concerns\HasContainer;

    private function hasIndirectTraitUse(ClassReflection $class, string $traitName): bool
    {
        foreach ($class->getTraits() as $trait) {
            if ($this->hasIndirectTraitUse($trait, $traitName)) {
                return true;
            }
        }

        return $class->hasTraitUse($traitName);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $className = null;
        $found = false;
        $macroTraitProperty = null;

        if ($classReflection->isInterface()) {
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $className = get_class($concrete);

                if ($passable->getBroker()
                    ->getClass($className)
                    ->hasTraitUse(Macroable::class)) {
                    $macroTraitProperty = 'macros';
                }
            }
        } elseif ($classReflection->hasTraitUse(Macroable::class)) {
            /** @var \Illuminate\Support\Traits\Macroable $macroable */
            $className = $classReflection->getName();
            $macroTraitProperty = 'macros';
        } elseif ($this->hasIndirectTraitUse($classReflection, CarbonMacro::class)) {
            /** @var \Illuminate\Support\Traits\Macroable $macroable */
            $className = $classReflection->getName();
            $macroTraitProperty = 'globalMacros';
        }

        if ($macroTraitProperty) {
            $refObject = new \ReflectionClass($className);
            $refProperty = $refObject->getProperty($macroTraitProperty);
            $refProperty->setAccessible(true);

            $className = (string) $className;

            if ($found = $className::hasMacro($passable->getMethodName())) {
                $reflectionFunction = new \ReflectionFunction($refProperty->getValue()[$passable->getMethodName()]);
                /** @var \PHPStan\Type\Type[] $parameters */
                $parameters = $reflectionFunction->getParameters();

                $passable->setMethodReflection(
                    $passable->getMethodReflectionFactory()
                        ->create(
                            $classReflection,
                            null,
                            new Macro(
                                $classReflection->getName(), $passable->getMethodName(), $reflectionFunction
                            ),
                            $parameters,
                            null,
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

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
use PhpParser\Node\Name;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Methods\Macro;
use Illuminate\Support\Traits\Macroable;
use PHPStan\Reflection\ParametersAcceptorSelector;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class Macros implements PipeContract
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
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

            $className = (string) $className;

            if ($found = $className::hasMacro($passable->getMethodName())) {
                $functionName = new Name($refProperty->getValue()[$passable->getMethodName()]);
                $broker = $passable->getBroker();
                if ($broker->hasFunction($functionName, null)) {
                    $functionReflection = $broker->getFunction($functionName, null);
                    $functionVariant = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());
                    $passable->setMethodReflection(
                        new Macro(
                            $classReflection,
                            $passable->getMethodName(),
                            $functionVariant->getParameters(),
                            $functionVariant->isVariadic(),
                            $functionVariant->getReturnType(),
                            false
                        )
                    );
                }
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

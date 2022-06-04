<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods\Pipes;

use function array_key_exists;
use Carbon\Traits\Macro as CarbonMacro;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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

        /** @var class-string $className */
        $className = null;
        $found = false;
        $macroTraitProperty = null;

        if ($classReflection->isInterface() && Str::startsWith($classReflection->getName(), 'Illuminate\Contracts')) {
            /** @var object|null $concrete */
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $className = get_class($concrete);

                if ($className && $passable->getReflectionProvider()
                    ->getClass($className)
                    ->hasTraitUse(Macroable::class)) {
                    $macroTraitProperty = 'macros';
                }
            }
        } elseif ($classReflection->hasTraitUse(Macroable::class) || $classReflection->getName() === Builder::class) {
            $className = $classReflection->getName();
            $macroTraitProperty = 'macros';
        } elseif ($this->hasIndirectTraitUse($classReflection, CarbonMacro::class)) {
            $className = $classReflection->getName();
            $macroTraitProperty = 'globalMacros';
        }

        if ($className !== null && $macroTraitProperty) {
            $classReflection = $passable->getReflectionProvider()->getClass($className);

            if ($classReflection->getNativeReflection()->hasProperty($macroTraitProperty)) {
                $refProperty = $classReflection->getNativeReflection()->getProperty($macroTraitProperty);
                $refProperty->setAccessible(true);

                $found = array_key_exists($passable->getMethodName(), $refProperty->getValue());

                if ($found) {
                    $reflectionFunction = new \ReflectionFunction($refProperty->getValue()[$passable->getMethodName()]);

                    $methodReflection = new Macro(
                        $classReflection, $passable->getMethodName(), $reflectionFunction
                    );

                    $methodReflection->setIsStatic(true);

                    $passable->setMethodReflection($methodReflection);
                }
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

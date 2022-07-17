<?php

namespace NunoMaduro\Larastan\Methods;

use Carbon\Traits\Macro as CarbonMacro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use NunoMaduro\Larastan\Concerns\HasContainer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureTypeFactory;

class MacroMethodsClassReflectionExtension implements \PHPStan\Reflection\MethodsClassReflectionExtension
{
    use HasContainer;

    /** @var array<string, MethodReflection> */
    private array $methods = [];

    public function __construct(private ReflectionProvider $reflectionProvider, private ClosureTypeFactory $closureTypeFactory)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        /** @var class-string $className */
        $className = null;
        $found = false;
        $macroTraitProperty = null;

        if ($classReflection->isInterface() && Str::startsWith($classReflection->getName(), 'Illuminate\Contracts')) {
            /** @var object|null $concrete */
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $className = get_class($concrete);

                if ($className && $this->reflectionProvider->getClass($className)->hasTraitUse(Macroable::class)) {
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
            $macroClassReflection = $this->reflectionProvider->getClass($className);

            if ($macroClassReflection->getNativeReflection()->hasProperty($macroTraitProperty)) {
                $refProperty = $macroClassReflection->getNativeReflection()->getProperty($macroTraitProperty);
                $refProperty->setAccessible(true);

                $found = array_key_exists($methodName, $refProperty->getValue());

                if ($found) {
                    $methodReflection = new Macro(
                        $macroClassReflection, $methodName, $this->closureTypeFactory->fromClosureObject($refProperty->getValue()[$methodName])
                    );

                    $methodReflection->setIsStatic(true);

                    $this->methods[$classReflection->getName().'-'.$methodName] = $methodReflection;
                }
            }
        }

        return $found;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        return $this->methods[$classReflection->getName().'-'.$methodName];
    }

    private function hasIndirectTraitUse(ClassReflection $class, string $traitName): bool
    {
        foreach ($class->getTraits() as $trait) {
            if ($this->hasIndirectTraitUse($trait, $traitName)) {
                return true;
            }
        }

        return $class->hasTraitUse($traitName);
    }
}

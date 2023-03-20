<?php

namespace NunoMaduro\Larastan\Methods;

use Carbon\Traits\Macro as CarbonMacro;
use Exception;
use Illuminate\Auth\RequestGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use NunoMaduro\Larastan\Concerns\HasContainer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ClosureTypeFactory;
use ReflectionException;

class MacroMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    use HasContainer;

    /** @var array<string, MethodReflection> */
    private array $methods = [];

    public function __construct(private ReflectionProvider $reflectionProvider, private ClosureTypeFactory $closureTypeFactory)
    {
    }

    /**
     * @throws ReflectionException
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        /** @var class-string[] $classNames */
        $classNames = [];
        $found = false;
        $macroTraitProperty = null;

        if ($classReflection->isInterface() && Str::startsWith($classReflection->getName(), 'Illuminate\Contracts')) {
            /** @var object|null $concrete */
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $className = get_class($concrete);

                if ($className && $this->reflectionProvider->getClass($className)->hasTraitUse(Macroable::class)) {
                    $classNames = [$className];
                    $macroTraitProperty = 'macros';
                }
            }
        } elseif ($this->hasIndirectTraitUse($classReflection, Macroable::class) || $classReflection->getName() === Builder::class || $classReflection->isSubclassOf(Builder::class)) {
            $classNames = [$classReflection->getName()];
            $macroTraitProperty = 'macros';

            if ($classReflection->isSubclassOf(Builder::class)) {
                $classNames[] = Builder::class;
            }
        } elseif ($this->hasIndirectTraitUse($classReflection, CarbonMacro::class)) {
            $classNames = [$classReflection->getName()];
            $macroTraitProperty = 'globalMacros';
        } elseif ($classReflection->isSubclassOf(Facade::class)) {
            $facadeClass = $classReflection->getName();

            if ($facadeClass === Auth::class) {
                $classNames = ['Illuminate\Auth\SessionGuard', RequestGuard::class];
                $macroTraitProperty = 'macros';
            } else {
                $concrete = null;

                try {
                    $concrete = $facadeClass::getFacadeRoot();
                } catch (Exception) {
                    //
                }

                if ($concrete) {
                    $facadeClassName = get_class($concrete);

                    if ($facadeClassName) {
                        $classNames = [$facadeClassName];
                        $macroTraitProperty = 'macros';
                    }
                }
            }
        }

        if ($classNames !== [] && $macroTraitProperty) {
            foreach ($classNames as $className) {
                $macroClassReflection = $this->reflectionProvider->getClass($className);

                if ($macroClassReflection->getNativeReflection()->hasProperty($macroTraitProperty)) {
                    $refProperty = $macroClassReflection->getNativeReflection()->getProperty($macroTraitProperty);
                    $refProperty->setAccessible(true);

                    $found = array_key_exists($methodName, $refProperty->getValue());

                    if ($found) {
                        $macroDefinition = $refProperty->getValue()[$methodName];

                        if (is_string($macroDefinition)) {
                            if (str_contains($macroDefinition, '::')) {
                                $macroDefinition = explode('::', $macroDefinition, 2);
                                $macroClassName = $macroDefinition[0];
                                if (! $this->reflectionProvider->hasClass($macroClassName) || ! $this->reflectionProvider->getClass($macroClassName)->hasNativeMethod($macroDefinition[1])) {
                                    throw new ShouldNotHappenException('Class '.$macroClassName.' does not exist');
                                }

                                $methodReflection = $this->reflectionProvider->getClass($macroClassName)->getNativeMethod($macroDefinition[1]);
                            } elseif (is_callable($macroDefinition)) {
                                $methodReflection = new Macro(
                                    $macroClassReflection, $methodName, $this->closureTypeFactory->fromClosureObject(\Closure::fromCallable($macroDefinition))
                                );
                            } else {
                                throw new ShouldNotHappenException('Function '.$macroDefinition.' does not exist');
                            }
                        } elseif (is_array($macroDefinition)) {
                            $macroClassName = get_class($macroDefinition[0]);
                            if ($macroClassName === false || ! $this->reflectionProvider->hasClass($macroClassName) || ! $this->reflectionProvider->getClass($macroClassName)->hasNativeMethod($macroDefinition[1])) {
                                throw new ShouldNotHappenException('Class '.$macroClassName.' does not exist');
                            }

                            $methodReflection = $this->reflectionProvider->getClass($macroClassName)->getNativeMethod($macroDefinition[1]);
                        } else {
                            $methodReflection = new Macro(
                                $macroClassReflection, $methodName, $this->closureTypeFactory->fromClosureObject($refProperty->getValue()[$methodName])
                            );

                            $methodReflection->setIsStatic(true);
                        }

                        $this->methods[$classReflection->getName().'-'.$methodName] = $methodReflection;

                        break;
                    }
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

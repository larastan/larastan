<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Closure;
use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Larastan\Larastan\Concerns\HasContainer;
use Larastan\Larastan\Methods\Macro;
use Larastan\Larastan\Methods\MacroClosureBodyResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ClosureTypeFactory;
use ReflectionException;
use Throwable;

use function array_key_exists;
use function array_keys;
use function explode;
use function get_class;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function str_contains;

/** @internal */
#[AutowiredService]
final class MacroHelper
{
    /** @var array<string, array<string, bool>> */
    private array $traitCache = [];

    /** @var array<string, array{class-string[], string|null}> */
    private array $macroSources = [];
    use HasContainer;

    public function __construct(private ReflectionProvider $reflectionProvider, private ClosureTypeFactory $closureTypeFactory, private MacroClosureBodyResolver $bodyResolver)
    {
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     * @throws ReflectionException
     */
    public function findMacro(ClassReflection $classReflection, string $methodName): MethodReflection|null
    {
        [$classNames, $macroTraitProperty] = $this->getMacroSources($classReflection);

        $found = false;

        if ($classNames !== [] && $macroTraitProperty) {
            foreach ($classNames as $className) {
                $macroClassReflection = $this->reflectionProvider->getClass($className);

                if (! $macroClassReflection->getNativeReflection()->hasProperty($macroTraitProperty)) {
                    continue;
                }

                $refProperty = $macroClassReflection->getNativeReflection()->getProperty($macroTraitProperty);
                $macros      = $refProperty->getValue();

                $found = array_key_exists($methodName, $macros);

                if (! $found) {
                    continue;
                }

                $macroDefinition = $macros[$methodName];

                if (is_string($macroDefinition)) {
                    if (str_contains($macroDefinition, '::')) {
                        $macroDefinition = explode('::', $macroDefinition, 2);
                        $macroClassName  = $macroDefinition[0];

                        if (! $this->reflectionProvider->hasClass($macroClassName) || ! $this->reflectionProvider->getClass($macroClassName)->hasNativeMethod($macroDefinition[1])) {
                            throw new ShouldNotHappenException('Class ' . $macroClassName . ' does not exist');
                        }

                        $methodReflection = $this->reflectionProvider->getClass($macroClassName)->getNativeMethod($macroDefinition[1]);
                    } elseif (is_callable($macroDefinition)) {
                        $methodReflection = new Macro(
                            $macroClassReflection,
                            $methodName,
                            $this->closureType(Closure::fromCallable($macroDefinition), $macroClassReflection, $methodName),
                        );
                    } else {
                        throw new ShouldNotHappenException('Function ' . $macroDefinition . ' does not exist');
                    }
                } elseif (is_array($macroDefinition)) {
                    if (is_string($macroDefinition[0])) {
                        $macroClassName = $macroDefinition[0];
                    } else {
                        $macroClassName = get_class($macroDefinition[0]);
                    }

                    if ($macroClassName === false || ! $this->reflectionProvider->hasClass($macroClassName) || ! $this->reflectionProvider->getClass($macroClassName)->hasNativeMethod($macroDefinition[1])) {
                        throw new ShouldNotHappenException('Class ' . $macroClassName . ' does not exist');
                    }

                    $methodReflection = $this->reflectionProvider->getClass($macroClassName)->getNativeMethod($macroDefinition[1]);
                } else {
                    $methodReflection = new Macro(
                        $macroClassReflection,
                        $methodName,
                        $this->closureType($macroDefinition, $macroClassReflection, $methodName),
                    );

                    $methodReflection->setIsStatic(true);
                }

                break;
            }
        }

        return $found ? $methodReflection : null;
    }

    /** @var array<string, ClosureType> */
    private array $closureTypes = [];

    private function closureType(Closure $closure, ClassReflection $classReflection, string $methodName): ClosureType
    {
        $key = $classReflection->getCacheKey() . '::' . $methodName;

        return $this->closureTypes[$key] ??= $this->bodyResolver->resolve($closure, $classReflection, $methodName)
            ?? $this->closureTypeFactory->fromClosureObject($closure);
    }

    /**
     * Determine which classes' macro definitions apply to the given class.
     *
     * This only depends on the class itself, so it is memoized — the trait
     * scans, container resolutions, and facade root lookups involved are
     * too expensive to repeat for every method lookup.
     *
     * @return array{class-string[], string|null}
     */
    private function getMacroSources(ClassReflection $classReflection): array
    {
        $cacheKey = $classReflection->getName();

        if (array_key_exists($cacheKey, $this->macroSources)) {
            return $this->macroSources[$cacheKey];
        }

        /** @var class-string[] $classNames */
        $classNames         = [];
        $macroTraitProperty = null;

        if ($classReflection->isInterface() && Str::startsWith($classReflection->getName(), 'Illuminate\Contracts')) {
            /** @var object|null $concrete */
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $className = $concrete::class;

                if ($className && $this->reflectionProvider->getClass($className)->hasTraitUse(Macroable::class)) {
                    $classNames         = [$className];
                    $macroTraitProperty = 'macros';
                }
            }
        } elseif (
            $this->hasIndirectTraitUse($classReflection, Macroable::class) ||
            $classReflection->is(Builder::class) ||
            $classReflection->is(QueryBuilder::class)
        ) {
            $classNames         = [$classReflection->getName()];
            $macroTraitProperty = 'macros';

            if ($classReflection->is(Builder::class)) {
                $classNames[] = Builder::class;
            }
        } elseif ($classReflection->is(Facade::class)) {
            $facadeClass = $classReflection->getName();

            if ($facadeClass === Auth::class) {
                $classNames         = [SessionGuard::class, RequestGuard::class];
                $macroTraitProperty = 'macros';
            } elseif ($facadeClass === Cache::class) {
                $classNames         = [CacheManager::class, CacheRepository::class];
                $macroTraitProperty = 'macros';
            } else {
                $concrete = null;

                try {
                    $concrete = $facadeClass::getFacadeRoot();
                } catch (Throwable) {
                }

                if ($concrete) {
                    $facadeClassName = $concrete::class;

                    if ($facadeClassName) {
                        $classNames         = [$facadeClassName];
                        $macroTraitProperty = 'macros';
                    }
                }
            }
        }

        return $this->macroSources[$cacheKey] = [$classNames, $macroTraitProperty];
    }

    private function hasIndirectTraitUse(ClassReflection $class, string $traitName): bool
    {
        $className = $class->getName();

        if (array_key_exists($className, $this->traitCache) && array_key_exists($traitName, $this->traitCache[$className])) {
            return $this->traitCache[$className][$traitName];
        }

        $this->traitCache[$className][$traitName] = in_array($traitName, array_keys($class->getTraits(true)), true);

        return $this->traitCache[$className][$traitName];
    }
}

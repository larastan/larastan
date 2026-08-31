<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Larastan\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;

use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function in_array;

final class EloquentBuilderForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var array<string, MethodReflection|null> */
    private array $cache = [];

    public function __construct(private BuilderHelper $builderHelper, private ReflectionProvider $reflectionProvider)
    {
    }

    /**
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $cacheKey = $classReflection->getCacheKey() . '-' . $methodName;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey] !== null;
        }

        return ($this->cache[$cacheKey] = $this->findMethod($classReflection, $methodName)) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $method = $this->cache[$classReflection->getCacheKey() . '-' . $methodName];

        assert($method !== null);

        return $method;
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    private function findMethod(ClassReflection $classReflection, string $methodName): MethodReflection|null
    {
        if (! $classReflection->is(EloquentBuilder::class)) {
            return null;
        }

        $loopReflection = $classReflection;

        do {
            $modelType = $loopReflection->getActiveTemplateTypeMap()->getType('TModel');

            if ($modelType !== null) {
                break;
            }

            $loopReflection = $loopReflection->getParentClass();
        } while ($loopReflection !== null);

        if ($modelType === null) {
            return null;
        }

        if ($modelType instanceof TemplateObjectType) {
            $modelType = $modelType->getBound();
        }

        $ref = $this->builderHelper->searchOnEloquentBuilder($classReflection, $methodName, $modelType);

        if ($ref === null) {
            // Special case for `SoftDeletes` trait
            if (
                in_array($methodName, ['withTrashed', 'onlyTrashed', 'withoutTrashed', 'restore', 'createOrRestore', 'restoreOrCreate'], true) &&
                array_key_exists(SoftDeletes::class, array_merge(...array_map(static fn ($r) => $r->getTraits(true), $modelType->getObjectClassReflections())))
            ) {
                $ref = $this->reflectionProvider->getClass(SoftDeletes::class)->getMethod($methodName, new OutOfClassScope());

                if ($methodName === 'restore') {
                    return new EloquentBuilderMethodReflection(
                        $methodName,
                        $classReflection,
                        $ref->getVariants()[0]->getParameters(),
                        new IntegerType(),
                        $ref->getVariants()[0]->isVariadic(),
                    );
                }

                if ($methodName === 'restoreOrCreate' || $methodName === 'createOrRestore') {
                    return new EloquentBuilderMethodReflection(
                        $methodName,
                        $classReflection,
                        $ref->getVariants()[0]->getParameters(),
                        $modelType,
                        $ref->getVariants()[0]->isVariadic(),
                    );
                }

                return new EloquentBuilderMethodReflection(
                    $methodName,
                    $classReflection,
                    $ref->getVariants()[0]->getParameters(),
                    $this->builderHelper->getBuilderType($classReflection->getName(), $modelType),
                    $ref->getVariants()[0]->isVariadic(),
                );
            }

            return null;
        }

        // Macros have their own reflection. And return type, parameters, etc. are already set with the closure.
        if ($ref instanceof Macro) {
            return $ref;
        }

        $parametersAcceptor = $ref->getVariants()[0];

        if ($classReflection->isGeneric()) {
            $returnType = new GenericObjectType($classReflection->getName(), array_values($classReflection->getTemplateTypeMap()->getTypes()));
        } else {
            $returnType = new ObjectType($classReflection->getName());
        }

        if (
            $ref->getDeclaringClass()->getName() === QueryBuilder::class
            && $this->reflectionProvider->getClass(QueryBuilder::class)->hasNativeMethod($methodName)
        ) {
            if (! in_array($methodName, $this->builderHelper->getPassthru(), true)) {
                return new EloquentBuilderMethodReflection(
                    $methodName,
                    $classReflection,
                    $parametersAcceptor->getParameters(),
                    $returnType,
                    $parametersAcceptor->isVariadic(),
                    true,
                );
            }

            $returnType = $parametersAcceptor->getReturnType();
        }

        // Returning custom reflection
        // to ensure return type is always `EloquentBuilder<Model>`
        return new EloquentBuilderMethodReflection(
            $methodName,
            $classReflection,
            $parametersAcceptor->getParameters(),
            $returnType,
            $parametersAcceptor->isVariadic(),
        );
    }
}

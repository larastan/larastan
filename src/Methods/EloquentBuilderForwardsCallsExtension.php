<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Larastan\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ThisType;

use function array_key_exists;
use function array_map;
use function array_merge;
use function in_array;

final class EloquentBuilderForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var array<string, MethodReflection> */
    private array $cache = [];

    /** @var list<string> */
    private array $softDeletesMethods = ['withTrashed', 'onlyTrashed', 'withoutTrashed', 'restore', 'createOrRestore', 'restoreOrCreate'];

    public function __construct(private BuilderHelper $builderHelper, private ReflectionProvider $reflectionProvider)
    {
    }

    /**
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (array_key_exists($classReflection->getCacheKey() . '-' . $methodName, $this->cache)) {
            return true;
        }

        $methodReflection = $this->findMethod($classReflection, $methodName);

        if ($methodReflection !== null) {
            $this->cache[$classReflection->getCacheKey() . '-' . $methodName] = $methodReflection;

            return true;
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getCacheKey() . '-' . $methodName];
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
                ! in_array($methodName, $this->softDeletesMethods, true) ||
                ! array_key_exists(SoftDeletes::class, array_merge(...array_map(static fn ($r) => $r->getTraits(true), $modelType->getObjectClassReflections())))
            ) {
                return null;
            }

            $ref = $this->reflectionProvider->getClass(SoftDeletes::class)->getMethod($methodName, new OutOfClassScope());

            if ($methodName === 'restore') {
                $returnType = new IntegerType();
            } elseif ($methodName === 'restoreOrCreate' || $methodName === 'createOrRestore') {
                $returnType = $modelType;
            } else {
                $returnType = new ThisType($classReflection);
            }

            return new EloquentBuilderMethodReflection(
                $methodName,
                $classReflection,
                $ref->getVariants()[0]->getParameters(),
                $returnType,
                $ref->getVariants()[0]->isVariadic(),
            );
        }

        // Macros have their own reflection. And return type, parameters, etc. are already set with the closure.
        if ($ref instanceof Macro) {
            return $ref;
        }

        $parametersAcceptor = $ref->getVariants()[0];
        $returnType         = $parametersAcceptor->getReturnType();

        if (! in_array($methodName, $this->builderHelper->passthru, true)) {
            $returnType = new ThisType($classReflection);
        }

        return new EloquentBuilderMethodReflection(
            $methodName,
            $classReflection,
            $parametersAcceptor->getParameters(),
            $returnType,
            $parametersAcceptor->isVariadic(),
        );
    }
}

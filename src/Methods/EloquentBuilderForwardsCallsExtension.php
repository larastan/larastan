<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Larastan\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;

use function array_key_exists;
use function in_array;

final class EloquentBuilderForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var array<string, MethodReflection> */
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
        if ($classReflection->getName() !== EloquentBuilder::class && ! $classReflection->isSubclassOf(EloquentBuilder::class)) {
            return null;
        }

        $modelType = $classReflection->getActiveTemplateTypeMap()->getType('TModelClass');

        // Generic type is not specified
        if ($modelType === null) {
            if (! $classReflection->isGeneric() && $classReflection->getParentClass()?->isGeneric()) {
                $modelType = $classReflection->getParentClass()->getActiveTemplateTypeMap()->getType('TModelClass');
            }
        }

        if ($modelType === null) {
            return null;
        }

        if ($modelType instanceof TemplateObjectType) {
            $modelType = $modelType->getBound();
        }

        if ($modelType->getObjectClassReflections() !== []) {
            $modelReflection = $modelType->getObjectClassReflections()[0];
        } else {
            $modelReflection = $this->reflectionProvider->getClass(Model::class);
        }

        $ref = $this->builderHelper->searchOnEloquentBuilder($classReflection, $methodName, $modelReflection);

        if ($ref === null) {
            // Special case for `SoftDeletes` trait
            if (
                in_array($methodName, ['withTrashed', 'onlyTrashed', 'withoutTrashed', 'restore', 'createOrRestore', 'restoreOrCreate'], true) &&
                array_key_exists(SoftDeletes::class, $modelReflection->getTraits(true))
            ) {
                $ref = $this->reflectionProvider->getClass(SoftDeletes::class)->getMethod($methodName, new OutOfClassScope());

                if ($methodName === 'restore') {
                    return new EloquentBuilderMethodReflection(
                        $methodName,
                        $classReflection,
                        ParametersAcceptorSelector::selectSingle($ref->getVariants())->getParameters(),
                        new IntegerType(),
                        ParametersAcceptorSelector::selectSingle($ref->getVariants())->isVariadic(),
                    );
                }

                if ($methodName === 'restoreOrCreate' || $methodName === 'createOrRestore') {
                    return new EloquentBuilderMethodReflection(
                        $methodName,
                        $classReflection,
                        ParametersAcceptorSelector::selectSingle($ref->getVariants())->getParameters(),
                        $modelType,
                        ParametersAcceptorSelector::selectSingle($ref->getVariants())->isVariadic(),
                    );
                }

                return new EloquentBuilderMethodReflection(
                    $methodName,
                    $classReflection,
                    ParametersAcceptorSelector::selectSingle($ref->getVariants())->getParameters(),
                    new GenericObjectType($classReflection->getName(), [$modelType]),
                    ParametersAcceptorSelector::selectSingle($ref->getVariants())->isVariadic(),
                );
            }

            return null;
        }

        $parametersAcceptor = ParametersAcceptorSelector::selectSingle($ref->getVariants());

        if (in_array($methodName, $this->builderHelper->passthru, true)) {
            $returnType = $parametersAcceptor->getReturnType();

            return new EloquentBuilderMethodReflection(
                $methodName,
                $classReflection,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic(),
            );
        }

        // Macros have their own reflection. And return type, parameters, etc. are already set with the closure.
        if ($ref instanceof Macro) {
            return $ref;
        }

        if ($classReflection->isGeneric()) {
            $returnType = new GenericObjectType($classReflection->getName(), [$modelType]);
        } else {
            $returnType = new ObjectType($classReflection->getName());
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

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use ReflectionClass;

final class EloquentBuilderForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /** @var array<string, MethodReflection> */
    private $cache = [];

    /** @var BuilderHelper */
    private $builderHelper;

    /** @var ReflectionProvider */
    private $reflectionProvider;

    public function __construct(BuilderHelper $builderHelper, ReflectionProvider $reflectionProvider)
    {
        $this->builderHelper = $builderHelper;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (array_key_exists($classReflection->getCacheKey().'-'.$methodName, $this->cache)) {
            return true;
        }

        $methodReflection = $this->findMethod($classReflection, $methodName);

        if ($methodReflection !== null && $classReflection->isGeneric()) {
            $this->cache[$classReflection->getCacheKey().'-'.$methodName] = $methodReflection;

            return true;
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getCacheKey().'-'.$methodName];
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    private function findMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
    {
        if ($classReflection->getName() !== EloquentBuilder::class && ! $classReflection->isSubclassOf(EloquentBuilder::class)) {
            return null;
        }

        /** @var Type|TemplateMixedType|null $modelType */
        $modelType = $classReflection->getActiveTemplateTypeMap()->getType('TModelClass');

        // Generic type is not specified
        if ($modelType === null) {
            return null;
        }

        if ($modelType instanceof TypeWithClassName) {
            $modelReflection = $modelType->getClassReflection();
        } else {
            $modelReflection = $this->reflectionProvider->getClass(Model::class);
        }

        if ($modelReflection === null) {
            return null;
        }

        $ref = $this->builderHelper->searchOnEloquentBuilder($classReflection, $methodName, $modelReflection);

        if ($ref === null) {
            // Special case for `SoftDeletes` trait
            if (
                in_array($methodName, ['withTrashed', 'onlyTrashed', 'withoutTrashed'], true) &&
                in_array(SoftDeletes::class, $this->collectTraitNames($modelReflection->getNativeReflection()))
            ) {
                $ref = $this->reflectionProvider->getClass(SoftDeletes::class)->getMethod($methodName, new OutOfClassScope());

                return new EloquentBuilderMethodReflection(
                    $methodName,
                    $classReflection,
                    $ref,
                    ParametersAcceptorSelector::selectSingle($ref->getVariants())->getParameters(),
                    new GenericObjectType($classReflection->getName(), [$modelType]),
                    ParametersAcceptorSelector::selectSingle($ref->getVariants())->isVariadic()
                );
            }

            return null;
        }

        $parametersAcceptor = ParametersAcceptorSelector::selectSingle($ref->getVariants());

        if (in_array($methodName, $this->builderHelper->passthru, true)) {
            $returnType = $parametersAcceptor->getReturnType();

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection,
                $ref,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        // Returning custom reflection
        // to ensure return type is always `EloquentBuilder<Model>`
        return new EloquentBuilderMethodReflection(
            $methodName, $classReflection,
            $ref,
            $parametersAcceptor->getParameters(),
            new GenericObjectType($classReflection->getName(), [$modelType]),
            $parametersAcceptor->isVariadic()
        );
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return string[]
     */
    private function collectTraitNames(ReflectionClass $class): array
    {
        $traits = [];
        $traitsLeftToAnalyze = $class->getTraits();

        while (count($traitsLeftToAnalyze) !== 0) {
            $trait = reset($traitsLeftToAnalyze);
            $traits[] = $trait->getName();

            foreach ($trait->getTraits() as $subTrait) {
                if (in_array($subTrait->getName(), $traits, \true)) {
                    continue;
                }

                $traitsLeftToAnalyze[] = $subTrait;
            }

            array_shift($traitsLeftToAnalyze);
        }

        return $traits;
    }
}

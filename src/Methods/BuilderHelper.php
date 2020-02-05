<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class BuilderHelper
{
    /** @var string[] */
    public const MODEL_RETRIEVAL_METHODS = ['first', 'find', 'findMany', 'findOrFail', 'firstOrFail'];

    /** @var string[] */
    public const MODEL_CREATION_METHODS = ['make', 'create', 'forceCreate', 'findOrNew', 'firstOrNew', 'updateOrCreate', 'fromQuery', 'firstOrCreate'];

    /**
     * @var Broker
     */
    private $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function dynamicWhere(
        string $methodName,
        Type $returnObject
    ): ?EloquentBuilderMethodReflection {
        if (! Str::startsWith($methodName, 'where')) {
            return null;
        }

        $classReflection = $this->broker->getClass(QueryBuilder::class);

        $methodReflection = $classReflection->getNativeMethod('dynamicWhere');

        /** @var FunctionVariantWithPhpDocs $originalDynamicWhereVariant */
        $originalDynamicWhereVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        return new EloquentBuilderMethodReflection(
            $methodName,
            $classReflection,
            [$originalDynamicWhereVariant->getParameters()[1]],
            $returnObject
        );
    }

    public function searchOnEloquentBuilder(string $methodName, string $modelClassName): ?MethodReflection
    {
        $model = $this->broker->getClass($modelClassName);

        if ($model->hasNativeMethod('scope'.ucfirst($methodName))) {
            $methodReflection = $model->getNativeMethod('scope'.ucfirst($methodName));
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

            $parameters = $parametersAcceptor->getParameters();
            // We shift the parameters,
            // because first parameter is the Builder
            array_shift($parameters);

            $returnType = $parametersAcceptor->getReturnType();

            return new EloquentBuilderMethodReflection(
                'scope'.ucfirst($methodName), $methodReflection->getDeclaringClass(),
                $parameters,
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        $eloquentBuilder = $this->broker->getClass(EloquentBuilder::class);

        if (! $eloquentBuilder->hasNativeMethod($methodName)) {
            return null;
        }

        if (in_array($methodName, array_merge(self::MODEL_CREATION_METHODS, self::MODEL_RETRIEVAL_METHODS), true)) {
            $methodReflection = $eloquentBuilder->getNativeMethod($methodName);
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
            $returnType = ModelTypeHelper::replaceStaticTypeWithModel($parametersAcceptor->getReturnType(), $modelClassName);

            return new EloquentBuilderMethodReflection(
                $methodName, $eloquentBuilder,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        return $eloquentBuilder->getNativeMethod($methodName);
    }

    public function searchOnQueryBuilder(string $methodName, string $modelClassName): ?MethodReflection
    {
        $queryBuilder = $this->broker->getClass(QueryBuilder::class);

        if ($queryBuilder->hasNativeMethod($methodName)) {
            return $queryBuilder->getNativeMethod($methodName);
        }

        return null;
    }

    /**
     * @param string $modelClassName
     *
     * @return string|null
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function determineBuilderType(string $modelClassName): ?string
    {
        $method = $this->broker->getClass($modelClassName)->getNativeMethod('newEloquentBuilder');

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        if (in_array(EloquentBuilder::class, $returnType->getReferencedClasses(), true)) {
            return null;
        }

        if (in_array(QueryBuilder::class, $returnType->getReferencedClasses(), true)) {
            return null;
        }

        if ($returnType instanceof ObjectType) {
            return $returnType->getClassName();
        }

        return $returnType->describe(VerbosityLevel::value());
    }

    public function getMethodReflectionFromBuilder(
        ClassReflection $classReflection,
        string $methodName,
        string $modelName,
        Type $customReturnType
    ): ?EloquentBuilderMethodReflection {
        $methodReflection = null;
        $model = $this->broker->getClass($modelName);

        // Check if model has a custom builder. If yes try to find the method there.
        $customBuilderName = $this->determineBuilderType($modelName);

        if ($customBuilderName !== null) {
            $customBuilder = $this->broker->getClass($customBuilderName);

            if ($customBuilder->hasNativeMethod($methodName)) {
                $methodReflection = $customBuilder->getNativeMethod($methodName);
            }
        }

        if ($methodReflection === null) {
            $methodReflection = $this->searchOnEloquentBuilder($methodName, $modelName);
        }

        if ($methodReflection === null) {
            $methodReflection = $this->searchOnQueryBuilder($methodName, $modelName);
        }

        if ($methodReflection !== null) {
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
            $returnType = $parametersAcceptor->getReturnType();

            // If a model scope has a void return type, return the builder
            if ($returnType instanceof VoidType && $model->hasNativeMethod('scope'.ucfirst($methodName))) {
                $returnType = $customReturnType;
            }

            if ($customBuilderName || count(array_intersect([EloquentBuilder::class, QueryBuilder::class], $returnType->getReferencedClasses())) > 0) {
                $returnType = $customReturnType;
            }

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        return $this->dynamicWhere($methodName, $customReturnType);
    }
}

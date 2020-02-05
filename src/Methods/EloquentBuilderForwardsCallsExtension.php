<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EloquentBuilderForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * The methods that should be returned from query builder.
     *
     * @var string[]
     */
    protected $passthru = [
        'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing', 'getBindings', 'toSql', 'dump', 'dd',
        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum', 'getConnection',
    ];

    private function getBuilderReflection(): ClassReflection
    {
        return $this->broker->getClass(QueryBuilder::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== EloquentBuilder::class && ! $classReflection->isSubclassOf(EloquentBuilder::class)) {
            return false;
        }

        if (in_array($methodName, $this->passthru, true)) {
            return true;
        }

        if ($this->getBuilderReflection()->hasNativeMethod($methodName)) {
            return true;
        }

        $templateTypeMap = $classReflection->getActiveTemplateTypeMap();

        if (! $templateTypeMap->getType('TModelClass') instanceof ObjectType) {
            return false;
        }

        return true;
    }

    /**
     * @throws ShouldNotHappenException
     * @throws \PHPStan\Reflection\MissingMethodFromReflectionException
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        if (in_array($methodName, $this->passthru, true)) {
            $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);

            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
            $returnType = $parametersAcceptor->getReturnType();

            if ($returnType instanceof MixedType) {
                $returnType = $returnType->subtract(new ObjectType(EloquentBuilder::class));
            }

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        $templateTypeMap = $classReflection->getActiveTemplateTypeMap();

        /** @var Type|ObjectType|TemplateMixedType $modelType */
        $modelType = $templateTypeMap->getType('TModelClass');

        if ($this->getBuilderReflection()->hasNativeMethod($methodName) && (! $modelType instanceof ObjectType)) {
            $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);
            $builderClass = EloquentBuilder::class;

            if ($modelType instanceof TemplateMixedType) {
                /** @var string $builderClass */
                $builderClass = $modelType->getScope()->getClassName();
            }

            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection,
                $parametersAcceptor->getParameters(),
                new GenericObjectType($builderClass, [$modelType]),
                $parametersAcceptor->isVariadic()
            );
        }

        if ($modelType instanceof ObjectType) {
            $builderHelper = new BuilderHelper($this->getBroker());
            $customBuilderName = $builderHelper->determineBuilderType($modelType->getClassName());
            $returnMethodReflection = $builderHelper->getMethodReflectionFromBuilder(
                $classReflection,
                $methodName,
                $modelType->getClassName(),
                new GenericObjectType($customBuilderName ?? EloquentBuilder::class, [new ObjectType($modelType->getClassName())])
            );

            if ($returnMethodReflection !== null) {
                return $returnMethodReflection;
            }
        }

        return new DummyMethodReflection($methodName);
    }
}

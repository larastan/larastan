<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function count;
use function in_array;

class EloquentCollectionMapDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Collection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['map', 'mapWithKeys'], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        if (count($args) !== 1) {
            return null;
        }

        $callableArg = $scope->getType($args[0]->value);

        if (! $callableArg->isCallable()->yes()) {
            return null;
        }

        $calledOnType = $scope->getType($methodCall->var);

        if ($calledOnType->getObjectClassNames() === []) {
            return null;
        }

        $keyType   = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TKey');
        $valueType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModel');

        if ($keyType === null || $valueType === null) {
            return null;
        }

        $callableReturnType = $callableArg->getCallableParametersAcceptors($scope)[0]->getReturnType();

        if ($methodReflection->getName() === 'mapWithKeys') {
            $keyType            = $callableReturnType->getIterableKeyType();
            $callableReturnType = $callableReturnType->getIterableValueType();
        }

        if ((new ObjectType(Model::class))->isSuperTypeOf($callableReturnType)->yes()) {
            if (! $calledOnType->getObjectClassReflections()[0]->isGeneric()) {
                return $calledOnType;
            }

            return new GenericObjectType($calledOnType->getObjectClassNames()[0], [$keyType, $callableReturnType]);
        }

        return new GenericObjectType(\Illuminate\Support\Collection::class, [$keyType, $callableReturnType]);
    }
}

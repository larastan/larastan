<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Support\Enumerable;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class CollectionWhereNotNullDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'whereNotNull';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $calledOnType = $scope->getType($methodCall->var);

        if (! $calledOnType instanceof \PHPStan\Type\Generic\GenericObjectType) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $keyType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TKey');
        $valueType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TValue');

        if ($keyType === null || $valueType === null) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $nonFalseyTypes = TypeCombinator::removeNull($valueType);

        return new GenericObjectType($calledOnType->getClassName(), [$keyType, $nonFalseyTypes]);
    }
}

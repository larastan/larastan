<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes\Helpers;

use Illuminate\Database\Eloquent\FactoryBuilder;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;

final class FactoryHelper implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return FactoryBuilder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'create';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): \PHPStan\Type\Type {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        $typeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        $amountType = $typeMap->getType('TAmount');
        $modelType = $typeMap->getType('TModel');

        // $amountType and $modelType should not be null here. Maybe add a check

        if ($amountType instanceof NullType) {
            return $modelType;
        }

        if ($amountType instanceof IntegerType) {
            return TypeCombinator::remove($returnType, $modelType);
        }

        return $returnType;
    }
}

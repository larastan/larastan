<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use ArrayAccess;
use Illuminate\Support\Arr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PhpParser\Node\Expr\StaticCall;

/**
 * @internal
 */
final class ArrGetPullExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Arr::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get' || $methodReflection->getName() === 'pull';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): ?Type {
        if (count($methodCall->getArgs()) <= 1) {
            return null;
        }

        $arrayType = $scope->getType($methodCall->getArgs()[0]->value);
        $offsetType = $scope->getType($methodCall->getArgs()[1]->value);

        if ($offsetType->isNull()->yes()) {
            return $arrayType;
        }

        $hasOffsetValue = $arrayType->hasOffsetValueType($offsetType);

        if ($hasOffsetValue->yes()) {
            return $arrayType->getOffsetValueType($offsetType);
        }

        $defaultType = new NullType();

        if (count($methodCall->getArgs()) === 3) {
            $defaultType = $scope->getType($methodCall->getArgs()[2]->value);
        }

        if ($hasOffsetValue->no()) {
            return $defaultType;
        }

        $returnTypes = [$defaultType];

        $arrayAccessType = new ObjectType(ArrayAccess::class);
        if ($arrayType->isObject()->yes() && $arrayAccessType->isSuperTypeOf($arrayType)->yes()) {
            $returnTypes[] = $arrayType->getTemplateType(ArrayAccess::class, 'TValue');
        } else {
            $returnTypes[] = $arrayType->getOffsetValueType($offsetType);
        }

        if ($offsetType->isNull()->maybe()) {
            $returnTypes[] = $arrayType;
        }

        return TypeCombinator::union(...$returnTypes);
    }
}

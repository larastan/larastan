<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Support\Enumerable;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

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
    ): ?Type {
        $calledOnType = $scope->getType($methodCall->var);

        if ($calledOnType->getObjectClassNames() === []) {
            return null;
        }

        $keyType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TKey');
        $valueType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TValue') ??
            $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModel');

        if ($keyType === null || $valueType === null) {
            return null;
        }

        $nonFalseyTypes = TypeCombinator::removeNull($valueType);

        if (! $this->argumentIsString($methodCall, $scope)) {
            return new GenericObjectType($calledOnType->getObjectClassNames()[0], [$keyType, $nonFalseyTypes]);
        }

        $scalarTypes = new UnionType([
            new StringType(),
            new IntegerType(),
            new FloatType(),
            new BooleanType(),
        ]);

        $nonFalseyTypes = TypeCombinator::remove($nonFalseyTypes, $scalarTypes);

        return new GenericObjectType($calledOnType->getObjectClassNames()[0], [$keyType, $nonFalseyTypes]);
    }

    public function argumentIsString(MethodCall $methodCall, Scope $scope): bool
    {
        if (count($methodCall->getArgs()) === 0) {
            return false;
        }

        $argType = $scope->getType($methodCall->getArgs()[0]->value);

        return (new NullType())->isSuperTypeOf($argType)->no();
    }
}

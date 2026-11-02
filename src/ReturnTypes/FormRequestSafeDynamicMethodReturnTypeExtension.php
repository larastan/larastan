<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Foundation\Http\FormRequest;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

class FormRequestSafeDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return FormRequest::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'safe';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $argType = $scope->getType($args[0]->value);

        if (! $argType->isConstantArray()->yes()) {
            return null;
        }

        $types = [];

        foreach ($argType->getConstantArrays() as $constantArray) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            foreach ($constantArray->getValueTypes() as $keyType) {
                foreach ($keyType->getConstantStrings() as $constantString) {
                    $builder->setOffsetValueType($constantString, new MixedType());
                }
            }

            $types[] = $builder->getArray();
        }

        return TypeCombinator::union(...$types);
    }
}

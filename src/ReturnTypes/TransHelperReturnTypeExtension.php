<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/** @internal */
#[AutowiredService]
final class TransHelperReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'trans';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type|null {
        $translationType = new BenevolentUnionType([
            new ArrayType(new MixedType(), new MixedType()),
            new StringType(),
        ]);

        $keyType = ParametersAcceptorSelector::selectFromArgs($scope, $functionCall->getArgs(), $functionReflection->getVariants(), $functionReflection->getNamedArgumentsVariants())
            ->getResolvedTemplateTypeMap()->getType('TKey');

        if ($keyType === null) {
            return null;
        }

        if ($keyType->isNull()->yes()) {
            return TypeCombinator::remove($functionReflection->getVariants()[0]->getNativeReturnType(), $translationType);
        }

        return $keyType->isNull()->no() ? $translationType : null;
    }
}

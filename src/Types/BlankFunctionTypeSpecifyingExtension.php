<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class BlankFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    /** @var TypeSpecifier */
    private $typeSpecifier;

    public function isFunctionSupported(
        FunctionReflection $functionReflection,
        FuncCall $node,
        TypeSpecifierContext $context
    ): bool {
        return $functionReflection->getName() === 'blank';
    }

    public function specifyTypes(
        FunctionReflection $functionReflection,
        FuncCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        $expr = $node->getArgs()[0]->value;
        $typeBefore = $scope->getType($expr);

        $falseyTypes = $this->getFalseyTypes();

        if ($context->null()) {
            return $this->typeSpecifier->create($expr, $typeBefore, TypeSpecifierContext::createTruthy());
        }

        if ($this->isCalledOnIterable($typeBefore)) {
            if ($context->falsey()) {
                $typeBefore = TypeCombinator::removeNull($typeBefore);
            }

            return $this->typeSpecifier->create($expr, $typeBefore, TypeSpecifierContext::createTruthy());
        }

        if ($context->falsey()) {
            $nonFalseyTypes = TypeCombinator::remove($typeBefore, $falseyTypes);

            return $this->typeSpecifier->create($expr, $nonFalseyTypes, TypeSpecifierContext::createTruthy());
        }

        return $this->typeSpecifier->create($expr, $falseyTypes, TypeSpecifierContext::createTruthy());
    }

    protected function isCalledOnIterable(Type $type): bool
    {
        if ($type->isIterable()->yes() && (! $type->isArray()->yes())) {
            return true;
        }

        if ($type instanceof UnionType) {
            return (bool) collect($type->getTypes())->first(function ($innerType) {
                return $innerType->isIterable()->yes() && (! $innerType->isArray()->yes());
            });
        }

        return false;
    }

    private function getFalseyTypes(): UnionType
    {
        return new UnionType([
            new NullType(),
            new ConstantBooleanType(false),
            new ConstantStringType(''),
            new ConstantStringType(' '),
            new ConstantArrayType([], []),
        ]);
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
}

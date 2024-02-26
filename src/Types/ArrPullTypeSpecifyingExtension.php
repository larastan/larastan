<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Support\Arr;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PhpParser\Node\Expr\StaticCall;

class ArrPullTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function getClass(): string
    {
        return Arr::class;
    }

    public function isStaticMethodSupported(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        TypeSpecifierContext $context
    ): bool {
        return $staticMethodReflection->getName() === 'pull' && $context->null() && count($node->getArgs()) > 1;
    }

    public function specifyTypes(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        $offsetType = $scope->getType($node->getArgs()[1]->value);

        if ($offsetType->isConstantValue()->no()) {
            return new SpecifiedTypes();
        }

        $arrayType = $scope->getType($node->getArgs()[0]->value);

        if (!$arrayType->isArray()->yes()) {
            return new SpecifiedTypes();
        }

        return $this->typeSpecifier->create(
            $node->getArgs()[0]->value,
            $arrayType->unsetOffset($offsetType),
            TypeSpecifierContext::createTruthy(),
            true
        );
    }
}

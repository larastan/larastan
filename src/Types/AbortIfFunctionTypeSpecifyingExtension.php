<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

/*
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\FunctionTypeSpecifyingExtension;

final class AbortIfFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    /** @var TypeSpecifier */
    private $typeSpecifier;

    /** @var bool */
    protected $negate;

    public function __construct(bool $negate)
    {
        $this->negate = $negate;
    }

    public function isFunctionSupported(
        FunctionReflection $functionReflection,
        FuncCall $node,
        TypeSpecifierContext $context
    ): bool {
        $methodName = $this->negate === false ? 'abort_if' : 'abort_unless';

        return $functionReflection->getName() === $methodName && $context->null();
    }

    public function specifyTypes(
        FunctionReflection $functionReflection,
        FuncCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        if (count($node->args) < 2) {
            return new SpecifiedTypes();
        }

        $context = $this->negate === false ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createTruthy();

        return $this->typeSpecifier->specifyTypesInCondition($scope, $node->args[0]->value, $context);
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
}

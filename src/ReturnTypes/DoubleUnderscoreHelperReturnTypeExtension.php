<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class DoubleUnderscoreHelperReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function __construct(
        private TranslatorHelper $translatorHelper
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === '__';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        if (count($functionCall->args) === 0) {
            return new NullType();
        }

        return $this->translatorHelper->resolveTypeFromCall($functionReflection, $functionCall, $scope);
    }
}

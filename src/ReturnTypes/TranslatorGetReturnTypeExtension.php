<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Contracts\Translation\Translator;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class TranslatorGetReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private TranslatorHelper $translatorHelper
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Translator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        return $this->translatorHelper->resolveTypeFromCall($methodReflection, $methodCall, $scope);
    }
}

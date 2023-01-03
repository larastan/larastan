<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Contracts\Foundation\Application;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class ApplicationMakeDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private AppMakeHelper $appMakeHelper
    ) {
    }

    public function getClass(): string
    {
        return Application::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['make', 'makeWith', 'resolve'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        return $this->appMakeHelper->resolveTypeFromCall($methodCall, $scope);
    }
}

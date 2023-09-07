<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Support\Facades\App;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class AppMakeDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(
        private AppMakeHelper $appMakeHelper
    ) {
    }

    public function getClass(): string
    {
        return App::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'make' || $methodReflection->getName() === 'makeWith';
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        return $this->appMakeHelper->resolveTypeFromCall($methodCall, $scope);
    }
}

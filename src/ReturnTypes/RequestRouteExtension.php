<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

use function count;

/** @internal */
final class RequestRouteExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Request::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'route';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        if (count($methodCall->getArgs()) === 0) {
            return TypeCombinator::addNull(new ObjectType(Route::class));
        }

        if (count($methodCall->getArgs()) === 2) {
            $defaultType = $scope->getType($methodCall->getArgs()[1]->value);
        } else {
            $defaultType = new NullType();
        }

        return TypeUtils::toBenevolentUnion(TypeCombinator::union(
            new ObjectWithoutClassType(),
            new StringType(),
            $defaultType,
        ));
    }
}

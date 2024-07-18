<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\HigherOrderTapProxy;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

use function count;

/** @internal */
final class HigherOrderTapProxyExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return HigherOrderTapProxy::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return true;
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $type = $scope->getType($methodCall->var);

        /** @phpstan-ignore phpstanApi.instanceofType (deprecated) */
        if ($type instanceof GenericObjectType) {
            $types = $type->getTypes();

            if (count($types) === 1 && $types[0]->getObjectClassNames() !== []) {
                return $types[0];
            }
        }

        return new MixedType();
    }
}

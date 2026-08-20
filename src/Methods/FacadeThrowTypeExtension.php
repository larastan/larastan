<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Support\Facades\Facade;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;

/**
 * Gives a facade static call the throw type of the real method behind it.
 *
 * A call like `View::first()` forwards to its facade root
 * (`Illuminate\View\Factory::first()`), which declares `@throws InvalidArgumentException`.
 * PHPStan does not follow that forward for throw types: it sees only
 * `Facade::__callStatic()`, whose `@throws \RuntimeException` covers the "facade root has
 * not been set" case. So the call looks like it throws only RuntimeException, and catching
 * the real exception is wrongly reported as a dead catch (`catch.neverThrown`).
 *
 * This resolves the facade root method and returns its real throw type, unioned with the
 * __callStatic throw type so that failure mode is not lost.
 */
final class FacadeThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getDeclaringClass()->is(Facade::class);
    }

    public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type|null
    {
        $facadeClass = $methodReflection->getDeclaringClass()->getName();

        try {
            $root = $facadeClass::getFacadeRoot();
        } catch (Throwable) {
            return null;
        }

        if ($root === null) {
            return null;
        }

        $rootClass = $root::class;

        if (! $this->reflectionProvider->hasClass($rootClass)) {
            return null;
        }

        $rootReflection = $this->reflectionProvider->getClass($rootClass);
        $methodName     = $methodReflection->getName();

        if (! $rootReflection->hasNativeMethod($methodName)) {
            return null;
        }

        // What the real method behind the facade declares it throws.
        $rootThrowType = $rootReflection->getNativeMethod($methodName)->getThrowType();

        if ($rootThrowType === null) {
            return null;
        }

        // What every facade call can still throw via the magic Facade::__callStatic().
        $callStaticThrowType = $this->callStaticThrowType();

        if ($callStaticThrowType === null) {
            return $rootThrowType;
        }

        return TypeCombinator::union($rootThrowType, $callStaticThrowType);
    }

    private function callStaticThrowType(): Type|null
    {
        if (! $this->reflectionProvider->hasClass(Facade::class)) {
            return null;
        }

        $facadeReflection = $this->reflectionProvider->getClass(Facade::class);

        if (! $facadeReflection->hasNativeMethod('__callStatic')) {
            return null;
        }

        return $facadeReflection->getNativeMethod('__callStatic')->getThrowType();
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Support\HigherOrderTapProxy;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;

final class HigherOrderTapProxyExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== HigherOrderTapProxy::class) {
            return false;
        }

        $templateTypeMap = $classReflection->getActiveTemplateTypeMap();

        $templateType = $templateTypeMap->getType('TClass');

        if (! $templateType instanceof ObjectType && ! $templateType instanceof ThisType) {
            return false;
        }

        return $templateType->hasMethod($methodName)->yes();
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        /** @var ObjectType|ThisType $templateType */
        $templateType = $classReflection->getActiveTemplateTypeMap()->getType('TClass');

        if ($templateType instanceof ThisType) {
            $templateType = $templateType->getStaticObjectType();
        }

        $reflection = $templateType->getClassReflection();

        if ($reflection !== null) {
            return $reflection->getMethod($methodName, new OutOfClassScope());
        }

        return new DummyMethodReflection($methodName);
    }
}

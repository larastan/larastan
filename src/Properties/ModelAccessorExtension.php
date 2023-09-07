<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;

/**
 * @internal
 */
final class ModelAccessorExtension implements PropertiesClassReflectionExtension
{
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        $camelCase = Str::camel($propertyName);

        if ($classReflection->hasNativeMethod($camelCase)) {
            $methodReflection = $classReflection->getNativeMethod($camelCase);

            if ($methodReflection->isPublic() || $methodReflection->isPrivate()) {
                return false;
            }

            $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if ($returnType->getObjectClassReflections() === [] || ! $returnType->getObjectClassReflections()[0]->isGeneric()) {
                return false;
            }

            if (! (new GenericObjectType(Attribute::class, [new MixedType(), new MixedType()]))->isSuperTypeOf($returnType)->yes()) {
                return false;
            }

            return true;
        }

        return $classReflection->hasNativeMethod('get'.Str::studly($propertyName).'Attribute');
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName
    ): PropertyReflection {
        $studlyName = Str::studly($propertyName);

        if ($classReflection->hasNativeMethod($studlyName)) {
            $methodReflection = $classReflection->getNativeMethod($studlyName);

            /** @var GenericObjectType $returnType */
            $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            return new ModelProperty(
                $classReflection,
                $returnType->getTypes()[0],
                $returnType->getTypes()[1]
            );
        }

        $method = $classReflection->getNativeMethod('get'.Str::studly($propertyName).'Attribute');

        return new ModelProperty(
            $classReflection,
            $method->getVariants()[0]->getReturnType(),
            $method->getVariants()[0]->getReturnType()
        );
    }
}

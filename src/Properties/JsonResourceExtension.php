<?php

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Http\Resources\Json\JsonResource;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Type\ObjectType;

class JsonResourceExtension implements PropertiesClassReflectionExtension, MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->isSubclassOf(JsonResource::class)) {
            return false;
        }

        $modelClassReflection = $this->getModelClassReflection($classReflection);

        if (! $modelClassReflection) {
            return false;
        }

        return $modelClassReflection->hasMethod($methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): \PHPStan\Reflection\MethodReflection
    {
        $modelClassReflection = $this->getModelClassReflection($classReflection);

        if (! $modelClassReflection) {
            return new DummyMethodReflection($methodName);
        }

        return $modelClassReflection->getMethod($methodName, new OutOfClassScope());
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(JsonResource::class)) {
            return false;
        }

        $modelClassReflection = $this->getModelClassReflection($classReflection);

        if (! $modelClassReflection) {
            return false;
        }

        return $modelClassReflection->hasProperty($propertyName);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): \PHPStan\Reflection\PropertyReflection
    {
        $modelClassReflection = $this->getModelClassReflection($classReflection);

        if (! $modelClassReflection) {
            return new DummyPropertyReflection();
        }

        return $modelClassReflection->getProperty($propertyName, new OutOfClassScope());
    }

    private function getModelClassReflection(ClassReflection $classReflection): ?ClassReflection
    {
        $templateTypeMap = $classReflection->getParents()[0]->getActiveTemplateTypeMap();

        $modelType = $templateTypeMap->getType('TModelClass');

        if (! $modelType || ! $modelType instanceof ObjectType || ! $modelType->getClassReflection()) {
            return null;
        }

        return $modelType->getClassReflection();
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;

/**
 * @internal
 */
final class AnnotationExtension implements MethodsClassReflectionExtension, PropertiesClassReflectionExtension
{
    use Concerns\HasBroker;

    /** @var AnnotationsMethodsClassReflectionExtension */
    private $annotationsMethodsClassReflectionExtension;

    /** @var AnnotationsPropertiesClassReflectionExtension */
    private $annotationsPropertiesClassReflectionExtension;

    public function __construct(
        AnnotationsMethodsClassReflectionExtension $annotationsMethodsClassReflectionExtension,
        AnnotationsPropertiesClassReflectionExtension $annotationsPropertiesClassReflectionExtension
    ) {
        $this->annotationsMethodsClassReflectionExtension = $annotationsMethodsClassReflectionExtension;
        $this->annotationsPropertiesClassReflectionExtension = $annotationsPropertiesClassReflectionExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->annotationsMethodsClassReflectionExtension->hasMethod($classReflection, $methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->annotationsMethodsClassReflectionExtension->getMethod($classReflection, $methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $this->annotationsPropertiesClassReflectionExtension->hasProperty($classReflection, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->annotationsPropertiesClassReflectionExtension->getProperty($classReflection, $propertyName);
    }
}

<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Methods;

use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

/**
 * @internal
 */
final class AnnotationExtension implements MethodsClassReflectionExtension
{
    use Concerns\HasBroker;

    /** @var AnnotationsMethodsClassReflectionExtension */
    private $annotationsMethodsClassReflectionExtension;

    public function __construct(AnnotationsMethodsClassReflectionExtension $annotationsMethodsClassReflectionExtension)
    {
        $this->annotationsMethodsClassReflectionExtension = $annotationsMethodsClassReflectionExtension;
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
}

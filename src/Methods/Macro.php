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

use PHPStan\Type\Type;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ClassMemberReflection;

final class Macro implements MethodReflection
{
    /**
     * The class name.
     *
     * @var ClassReflection
     */
    private $declaringClass;

    /**
     * The method name.
     *
     * @var string
     */
    private $methodName;

    /**
     * @var \PHPStan\Reflection\ParameterReflection[]
     */
    private $parameters;

    /**
     * @var bool
     */
    private $isVariadic;

    /**
     * @var \PHPStan\Type\Type
     */
    private $returnType;

    /**
     * The is static.
     *
     * @var bool
     */
    private $isStatic;

    /**
     * Macro constructor.
     *
     * @param \PHPStan\Reflection\ClassReflection $declaringClass
     * @param string $methodName
     * @param \PHPStan\Reflection\ParameterReflection[] $parameters
     * @param bool $isVariadic
     * @param \PHPStan\Type\Type $returnType
     * @param bool $isStatic
     */
    public function __construct(ClassReflection $declaringClass, string $methodName, array $parameters, bool $isVariadic, Type $returnType, bool $isStatic)
    {
        $this->declaringClass = $declaringClass;
        $this->methodName = $methodName;
        $this->parameters = $parameters;
        $this->isVariadic = $isVariadic;
        $this->returnType = $returnType;
        $this->isStatic = $isStatic;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    /**
     * {@inheritdoc}
     */
    public function isPrivate(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isPublic(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->methodName;
    }

    public function getVariants(): array
    {
        return [
            new FunctionVariant($this->parameters, $this->isVariadic, $this->returnType),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }
}

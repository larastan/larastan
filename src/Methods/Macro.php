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

use PHPStan\Reflection\Php\BuiltinMethodReflection;
use ReflectionClass;

final class Macro implements BuiltinMethodReflection
{
    /**
     * The class name.
     *
     * @var string
     */
    private $className;

    /**
     * The method name.
     *
     * @var string
     */
    private $methodName;

    /**
     * The reflection function.
     *
     * @var \ReflectionFunction
     */
    private $reflectionFunction;

    /**
     * Macro constructor.
     *
     * @param string $className
     * @param string $methodName
     * @param \ReflectionFunction $reflectionFunction
     */
    public function __construct(string $className, string $methodName, \ReflectionFunction $reflectionFunction)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->reflectionFunction = $reflectionFunction;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->className);
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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocComment()
    {
        return $this->reflectionFunction->getDocComment();
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName()
    {
        return $this->reflectionFunction->getFileName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->methodName;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return $this->reflectionFunction->getParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnType(): ?\ReflectionType
    {
        return $this->reflectionFunction->getReturnType();
    }

    /**
     * {@inheritdoc}
     */
    public function getStartLine()
    {
        return $this->reflectionFunction->getStartLine();
    }

    /**
     * {@inheritdoc}
     */
    public function isDeprecated(): bool
    {
        return $this->reflectionFunction->isDeprecated();
    }

    /**
     * {@inheritdoc}
     */
    public function isVariadic(): bool
    {
        return $this->reflectionFunction->isVariadic();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrototype(): BuiltinMethodReflection
    {
        return $this;
    }
}

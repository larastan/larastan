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

namespace NunoMaduro\Larastan;

final class Macro extends \ReflectionMethod
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
        // parent::__construct($className, $methodName);

        $this->className = $className;
        $this->methodName = $methodName;
        $this->reflectionFunction = $reflectionFunction;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass()
    {
        return new \ReflectionClass($this->className);
    }

    /**
     * {@inheritdoc}
     */
    public function isAbstract()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isConstructor()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDestructor()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isFinal()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isPrivate()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isProtected()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isPublic()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessible($accessible)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function getClosureScopeClass()
    {
        return $this->reflectionFunction->getClosureScopeClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getClosureThis()
    {
        return $this->reflectionFunction->getClosureThis();
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
    public function getEndLine()
    {
        return $this->reflectionFunction->getEndLine();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension()
    {
        return $this->reflectionFunction->getExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionName()
    {
        return $this->reflectionFunction->getExtensionName();
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
    public function getName()
    {
        return $this->methodName;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaceName()
    {
        return (new \ReflectionClass($this->className))->getNamespaceName();
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberOfParameters()
    {
        return $this->reflectionFunction->getNumberOfParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberOfRequiredParameters()
    {
        return $this->reflectionFunction->getNumberOfRequiredParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->reflectionFunction->getParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnType()
    {
        return $this->reflectionFunction->getReturnType();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortName()
    {
        return $this->getName();
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
    public function getStaticVariables()
    {
        return $this->reflectionFunction->getStaticVariables();
    }

    /**
     * {@inheritdoc}
     */
    public function hasReturnType()
    {
        return $this->reflectionFunction->hasReturnType();
    }

    /**
     * {@inheritdoc}
     */
    public function inNamespace()
    {
        return (new \ReflectionClass($this->className))->inNamespace();
    }

    /**
     * {@inheritdoc}
     */
    public function isClosure()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDeprecated()
    {
        return $this->reflectionFunction->isDeprecated();
    }

    /**
     * {@inheritdoc}
     */
    public function isGenerator()
    {
        return $this->reflectionFunction->isGenerator();
    }

    /**
     * {@inheritdoc}
     */
    public function isInternal()
    {
        return $this->reflectionFunction->isInternal();
    }

    /**
     * {@inheritdoc}
     */
    public function isUserDefined()
    {
        return $this->reflectionFunction->isUserDefined();
    }

    /**
     * {@inheritdoc}
     */
    public function isVariadic()
    {
        return $this->reflectionFunction->isVariadic();
    }

    /**
     * {@inheritdoc}
     */
    public function returnsReference()
    {
        return $this->reflectionFunction->returnsReference();
    }
}
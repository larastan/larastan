<?php

declare(strict_types=1);

namespace Larastan\Larastan\Reflection;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class StaticMethodReflection implements MethodReflection
{
    public function __construct(private MethodReflection $methodReflection)
    {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->methodReflection->getDeclaringClass();
    }

    public function isStatic(): bool
    {
        return true;
    }

    public function isPrivate(): bool
    {
        return $this->methodReflection->isPrivate();
    }

    public function isPublic(): bool
    {
        return $this->methodReflection->isPublic();
    }

    public function getDocComment(): string|null
    {
        return $this->methodReflection->getDocComment();
    }

    public function getName(): string
    {
        return $this->methodReflection->getName();
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this->methodReflection->getPrototype();
    }

    /** @return ParametersAcceptor[] */
    public function getVariants(): array
    {
        return $this->methodReflection->getVariants();
    }

    public function isDeprecated(): TrinaryLogic
    {
        return $this->methodReflection->isDeprecated();
    }

    public function getDeprecatedDescription(): string|null
    {
        return $this->methodReflection->getDeprecatedDescription();
    }

    public function isFinal(): TrinaryLogic
    {
        return $this->methodReflection->isFinal();
    }

    public function isInternal(): TrinaryLogic
    {
        return $this->methodReflection->isInternal();
    }

    public function getThrowType(): Type|null
    {
        return $this->methodReflection->getThrowType();
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return $this->methodReflection->hasSideEffects();
    }
}

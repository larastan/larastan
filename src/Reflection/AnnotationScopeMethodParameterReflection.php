<?php

declare(strict_types=1);

namespace Larastan\Larastan\Reflection;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

final class AnnotationScopeMethodParameterReflection implements ParameterReflection
{
    public function __construct(private string $name, private Type $type, private PassedByReference $passedByReference, private bool $isOptional, private bool $isVariadic, private Type|null $defaultValue = null)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return $this->passedByReference;
    }

    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }

    public function getDefaultValue(): Type|null
    {
        return $this->defaultValue;
    }
}

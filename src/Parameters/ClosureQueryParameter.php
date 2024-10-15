<?php

declare(strict_types=1);

namespace Larastan\Larastan\Parameters;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

final class ClosureQueryParameter implements ParameterReflection
{
    public function __construct(
        private string $name,
        private Type $type,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return false;
    }

    public function getDefaultValue(): Type|null
    {
        return null;
    }
}

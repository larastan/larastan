<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use ReflectionNamedType;

/** @internal */
final class ReflectionTypeContainer extends ReflectionNamedType
{
    public function __construct(private string $type)
    {
    }

    public function allowsNull(): bool
    {
        return false;
    }

    public function isBuiltin(): bool
    {
        return false;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->type;
    }
}

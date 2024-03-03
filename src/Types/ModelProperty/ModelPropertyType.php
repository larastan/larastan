<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\ModelProperty;

use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class ModelPropertyType extends StringType
{
    /** @param  mixed[] $properties */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }
}

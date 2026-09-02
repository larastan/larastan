<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;

final readonly class ValidationRule
{
    /** @param list<ConstantIntegerType|ConstantStringType>|null $allowedKeys */
    public function __construct(
        public string $rule,
        public string $type,
        public bool $nullable = false,
        public bool $possiblyUndefined = false,
        public bool $required = false,
        public bool $benevolent = false,
        public Type|null $constraintType = null,
        public array|null $allowedKeys = null,
    ) {
    }
}

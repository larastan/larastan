<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;

final readonly class ValidationRule
{
    /**
     * @param list<ConstantIntegerType|ConstantStringType>|null $allowedKeys
     * @param list<list<ValidationRule>>                        $anyOfRuleGroups
     */
    public function __construct(
        public Type $type,
        public bool $nullable = false,
        public bool $possiblyUndefined = false,
        public bool $required = false,
        public Type|null $constraintType = null,
        public array|null $allowedKeys = null,
        public array $anyOfRuleGroups = [],
        public bool $rejectsNull = false,
    ) {
    }
}

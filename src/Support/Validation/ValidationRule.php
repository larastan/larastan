<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

final readonly class ValidationRule
{
    public function __construct(
        public string $rule,
        public string $type,
        public bool $nullable = false,
        public bool $possiblyUndefined = false,
        public bool $required = false,
    ) {
    }
}

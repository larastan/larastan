<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\Type\Type;

use function array_merge_recursive;

/**
 * One reading of the rules a field is validated with.
 *
 * @internal
 */
final class FieldRules
{
    /**
     * @param array<string, list<list<string>|null>> $rules Parameter lists by studly rule name; null when the parameters are unknown.
     * @param list<Type>                             $types Values accepted by rule objects.
     */
    public function __construct(public array $rules = [], public array $types = [])
    {
    }

    public function with(self $other): self
    {
        return new self(array_merge_recursive($this->rules, $other->rules), [...$this->types, ...$other->types]);
    }
}

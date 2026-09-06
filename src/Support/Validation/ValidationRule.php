<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;

use function count;

final readonly class ValidationRule
{
    /**
     * A null pruning flag means a bare array/list rule may or may not be present.
     *
     * @param list<ConstantIntegerType|ConstantStringType>|null     $allowedKeys
     * @param list<array{rules: list<ValidationRule>, anyOf: bool}> $anyOfRuleGroups
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
        public bool $possiblyExcluded = false,
        public bool $excluded = false,
        public bool $degraded = false,
        public bool|null $prunesUnvalidatedKeys = false,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->type->equals($other->type)
            && $this->nullable === $other->nullable
            && $this->possiblyUndefined === $other->possiblyUndefined
            && $this->required === $other->required
            && ($this->constraintType === null
                ? $other->constraintType === null
                : $other->constraintType !== null && $this->constraintType->equals($other->constraintType))
            && $this->rejectsNull === $other->rejectsNull
            && $this->possiblyExcluded === $other->possiblyExcluded
            && $this->excluded === $other->excluded
            && $this->degraded === $other->degraded
            && $this->prunesUnvalidatedKeys === $other->prunesUnvalidatedKeys
            && $this->hasSameStructure($other);
    }

    /** Compare the nested constraints that must agree when merging return paths. */
    public function hasSameStructure(self $other): bool
    {
        if ($this->allowedKeys === null || $other->allowedKeys === null) {
            if ($this->allowedKeys !== $other->allowedKeys) {
                return false;
            }
        } else {
            if (count($this->allowedKeys) !== count($other->allowedKeys)) {
                return false;
            }

            foreach ($this->allowedKeys as $index => $key) {
                if (! $key->equals($other->allowedKeys[$index])) {
                    return false;
                }
            }
        }

        if (count($this->anyOfRuleGroups) !== count($other->anyOfRuleGroups)) {
            return false;
        }

        foreach ($this->anyOfRuleGroups as $index => $group) {
            $otherGroup = $other->anyOfRuleGroups[$index];

            if ($group['anyOf'] !== $otherGroup['anyOf'] || count($group['rules']) !== count($otherGroup['rules'])) {
                return false;
            }

            foreach ($group['rules'] as $ruleIndex => $rule) {
                if (! $rule->equals($otherGroup['rules'][$ruleIndex])) {
                    return false;
                }
            }
        }

        return true;
    }
}

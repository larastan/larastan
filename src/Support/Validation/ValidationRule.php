<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

use function count;

final readonly class ValidationRule
{
    /**
     * @param list<ConstantIntegerType|ConstantStringType>|null     $allowedKeys
     * @param list<array{rules: list<ValidationRule>, anyOf: bool}> $anyOfRuleGroups
     */
    public function __construct(
        public Type $type,
        public Type|null $constraintType = null,
        public array|null $allowedKeys = null,
        public array $anyOfRuleGroups = [],
        public RuleFlags $flags = new RuleFlags(),
    ) {
    }

    /** Resolve the value type the rule guarantees on its own, before any nested rules refine it. */
    public function resolveType(bool $includeNullable = false): Type
    {
        $type = $this->type;

        if ($this->constraintType !== null) {
            $type = TypeCombinator::intersect($type, $this->constraintType);
        }

        foreach ($this->anyOfRuleGroups as $group) {
            $alternativeTypes = [];

            foreach ($group['rules'] as $alternative) {
                if ($alternative->flags->excluded) {
                    continue;
                }

                $alternativeTypes[] = $alternative->resolveType(true);
            }

            if ($alternativeTypes === []) {
                continue;
            }

            if ($group['anyOf']) {
                // AnyOf validates associative input directly, so even scalar list
                // alternatives can pass an array without constraining its other keys.
                $alternativeTypes[] = new ArrayType(new MixedType(), new MixedType());
            }

            $alternativeType = TypeCombinator::union(...$alternativeTypes);

            if ($group['anyOf']) {
                $alternativeType = TypeUtils::toBenevolentUnion($alternativeType);
            }

            $type = TypeCombinator::intersect($type, $alternativeType);
        }

        if ($this->flags->rejectsNull) {
            $type = TypeCombinator::removeNull($type);
        } elseif ($includeNullable && $this->flags->nullable) {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }

    /** The rules demand an array value, so a nested shape can be built for it. */
    public function isContainer(): bool
    {
        return $this->type->isArray()->yes();
    }

    /** The rules demand a known non-array value, so no array can ever pass them. */
    public function isScalarOnly(): bool
    {
        return ! $this->type->isArray()->yes() && ! $this->type->equals(new MixedType());
    }

    /** One of the rule's alternatives still permits an array value. */
    public function mayBeContainer(): bool
    {
        return $this->anyOfRuleGroups !== [] && ! $this->resolveType()->isArray()->no();
    }

    public function equals(self $other): bool
    {
        return $this->type->equals($other->type)
            && ($this->constraintType === null
                ? $other->constraintType === null
                : $other->constraintType !== null && $this->constraintType->equals($other->constraintType))
            && $this->flags->equals($other->flags)
            && $this->hasSameStructure($other);
    }

    /** Combine what two return paths guarantee for the same key, or null when their shapes disagree. */
    public function merge(self $other): self|null
    {
        if (! $this->hasSameStructure($other)) {
            return null;
        }

        $type = TypeCombinator::union($this->type, $other->type);

        if ($this->type instanceof BenevolentUnionType || $other->type instanceof BenevolentUnionType) {
            $type = TypeUtils::toBenevolentUnion($type);
        }

        return new self(
            type: $type,
            constraintType: $this->constraintType === null || $other->constraintType === null
                ? null
                : TypeCombinator::union($this->constraintType, $other->constraintType),
            allowedKeys: $this->allowedKeys,
            anyOfRuleGroups: $this->anyOfRuleGroups,
            flags: $this->flags->either($other->flags),
        );
    }

    /** Compare the nested constraints that must agree when merging return paths. */
    private function hasSameStructure(self $other): bool
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

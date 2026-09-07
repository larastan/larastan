<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use function array_slice;

/**
 * What a rule set says about a value beyond the type it constrains it to:
 * whether the value has to be present, may be null, or drops out of the
 * validated data entirely.
 *
 * @internal
 */
final readonly class RuleFlags
{
    /** A null pruning flag means a bare array/list rule may or may not be present. */
    public function __construct(
        public bool $nullable = false,
        public bool $possiblyUndefined = false,
        public bool $required = false,
        public bool $rejectsNull = false,
        public bool $possiblyExcluded = false,
        public bool $excluded = false,
        public bool $degraded = false,
        public bool|null $prunesUnvalidatedKeys = false,
    ) {
    }

    /** Combine two rule sets that both apply to the same value. */
    public function both(self $other): self
    {
        return new self(
            nullable: $this->nullable || $other->nullable,
            possiblyUndefined: $this->possiblyUndefined || $other->possiblyUndefined,
            required: $this->required || $other->required,
            rejectsNull: $this->rejectsNull || $other->rejectsNull,
            possiblyExcluded: $this->possiblyExcluded || $other->possiblyExcluded,
            excluded: $this->excluded || $other->excluded,
            degraded: $this->degraded || $other->degraded,
            prunesUnvalidatedKeys: self::combinePruning($this->prunesUnvalidatedKeys, $other->prunesUnvalidatedKeys),
        );
    }

    /** Combine two rule sets when only one of them applies and which one is unknown. */
    public function either(self $other): self
    {
        return new self(
            nullable: $this->nullable || $other->nullable,
            possiblyUndefined: $this->possiblyUndefined || $other->possiblyUndefined,
            required: $this->required && $other->required,
            rejectsNull: $this->rejectsNull && $other->rejectsNull,
            possiblyExcluded: $this->possiblyExcluded || $other->possiblyExcluded,
            excluded: $this->excluded && $other->excluded,
            degraded: $this->degraded || $other->degraded,
            prunesUnvalidatedKeys: $this->prunesUnvalidatedKeys === $other->prunesUnvalidatedKeys
                ? $this->prunesUnvalidatedKeys
                : null,
        );
    }

    /**
     * Collapse the alternatives of a conditional rule into the flags of the rule itself.
     *
     * @param non-empty-list<self> $alternatives
     */
    public static function fromAlternatives(array $alternatives): self
    {
        $flags = $alternatives[0];

        foreach (array_slice($alternatives, 1) as $alternative) {
            $flags = $flags->either($alternative);
        }

        return new self(
            // The alternatives stay in the rule's anyOf group and carry their own
            // nullability there, so the rule does not repeat it.
            possiblyUndefined: $flags->possiblyUndefined,
            // An alternative that may be absent or excluded cannot guarantee presence.
            required: $flags->required && ! $flags->possiblyUndefined && ! $flags->possiblyExcluded,
            rejectsNull: $flags->rejectsNull,
            possiblyExcluded: $flags->possiblyExcluded,
            excluded: $flags->excluded,
            degraded: $flags->degraded,
            prunesUnvalidatedKeys: $flags->prunesUnvalidatedKeys,
        );
    }

    /** A bare array/list rule anywhere in the set prunes, and an unknown one keeps it undecided. */
    public static function combinePruning(bool|null $left, bool|null $right): bool|null
    {
        if ($left === true || $right === true) {
            return true;
        }

        return $left === null || $right === null ? null : false;
    }

    public function equals(self $other): bool
    {
        return $this->nullable === $other->nullable
            && $this->possiblyUndefined === $other->possiblyUndefined
            && $this->required === $other->required
            && $this->rejectsNull === $other->rejectsNull
            && $this->possiblyExcluded === $other->possiblyExcluded
            && $this->excluded === $other->excluded
            && $this->degraded === $other->degraded
            && $this->prunesUnvalidatedKeys === $other->prunesUnvalidatedKeys;
    }
}

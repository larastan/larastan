<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_keys;
use function array_map;
use function array_shift;
use function count;
use function preg_split;
use function str_replace;

/**
 * Validation rules arranged by attribute path, and the array shapes they imply.
 *
 * Every node is one path segment; the root holds the top-level attributes.
 * Types are derived from two viewpoints. The request input only tells what
 * validation accepted. The validated() output is assembled by Laravel from
 * the attributes it validated, except where it copies an input value whole.
 *
 * @internal
 */
final class RuleTree
{
    public const WILDCARD = '*';

    /** The request input, as far as validation constrains it. */
    private const INPUT = 'input';

    /** The validated() output, assembled from the nested validated attributes. */
    private const VALIDATED = 'validated';

    /** The validated() output where Laravel copies the input value whole, minus excluded attributes. */
    private const COPIED = 'copied';

    /** As COPIED, but Laravel may assemble the value from nested attributes instead. */
    private const MAYBE_COPIED = 'maybe copied';

    public ValidationRule|null $rule = null;

    /** @var array<string, self> */
    public array $children = [];

    /** A wildcard and a named attribute share this level, so the shape below cannot be told apart. */
    public bool $degraded = false;

    /** Rules may exist for attributes the tree does not know. */
    public bool $unsealed = false;

    /** @param array<string, ValidationRule> $rules Keyed by attribute path, where a backslash escapes a literal dot. */
    public static function fromRules(array $rules, bool $unsealed): self
    {
        $root           = new self();
        $root->unsealed = $unsealed;

        foreach ($rules as $path => $rule) {
            $segments = array_map(
                static fn (string $segment): string => str_replace('\\.', '.', $segment),
                preg_split('/(?<!\\\\)\./', (string) $path) ?: [],
            );
            $node     = $root->children[array_shift($segments)] ??= new self();

            foreach ($segments as $segment) {
                $node->children[$segment] ??= new self();

                if (isset($node->children[self::WILDCARD]) && count($node->children) > 1) {
                    $node->degraded = true;
                }

                $node = $node->children[$segment];
            }

            $node->rule = $rule;
        }

        // A root wildcard can exclude or otherwise change every attribute.
        if (isset($root->children[self::WILDCARD])) {
            $root->children = [];
            $root->unsealed = true;
        }

        return $root;
    }

    /** @return array<string, Type> The top-level attributes as read from the request input. */
    public function inputProperties(): array
    {
        $types = [];

        foreach ($this->children as $name => $child) {
            $type = $child->type(self::INPUT);

            $types[$name] = $child->isPresent(validated: false) ? $type : TypeCombinator::addNull($type);
        }

        return $types;
    }

    public function validatedData(): Type
    {
        return $this->keyedShape(self::VALIDATED);
    }

    private function type(string $view): Type
    {
        $rule = $this->rule;

        if ($rule?->flags->degraded === true || ($view === self::INPUT && $rule?->flags->possiblyExcluded === true)) {
            return new MixedType();
        }

        // Nested rules never run against a scalar, so they cannot refine it.
        if ($rule?->isScalarOnly() === true) {
            return $this->leaf();
        }

        if ($view === self::VALIDATED && $this->copiesInput(certainly: false)) {
            $view = $this->copiesInput(certainly: true) ? self::COPIED : self::MAYBE_COPIED;
        }

        if ($rule?->allowedKeys === null && $this->children === []) {
            return $this->leaf();
        }

        $shape = match (true) {
            $rule?->allowedKeys !== null => $this->keyedShape($view),
            // Only the rule itself says anything about paths the tree does not model.
            $this->degraded => $rule?->resolveType() ?? new ArrayType(new MixedType(), new MixedType()),
            isset($this->children[self::WILDCARD]) => $this->wildcardShape($view),
            default => $this->keyedShape($view),
        };

        // An input value is only an array when its rule or a required nested attribute says so.
        if ($view !== self::VALIDATED && $rule?->isContainer() !== true) {
            $ruleType = $rule?->resolveType() ?? new MixedType();
            $shape    = TypeCombinator::intersect($shape, $ruleType);

            if (! $this->hasPresentChild(validated: false)) {
                $shape = TypeCombinator::union($shape, TypeCombinator::remove($ruleType, new ArrayType(new MixedType(), new MixedType())));
            }
        }

        return $this->nullable($shape);
    }

    private function keyedShape(string $view): Type
    {
        $builder     = ConstantArrayTypeBuilder::createEmpty();
        $validated   = $view === self::VALIDATED || $view === self::MAYBE_COPIED;
        $allowedKeys = $this->rule?->allowedKeys;
        $segments    = $allowedKeys === null
            ? array_keys($this->children)
            : array_map(static fn (ConstantIntegerType|ConstantStringType $key): string => (string) $key->getValue(), $allowedKeys);

        foreach ($segments as $segment) {
            $child = $this->children[$segment] ?? null;

            // Only validated attributes are assembled; only excluded ones vanish from a copy.
            if ($child === null ? $view === self::VALIDATED : $view !== self::INPUT && $child->rule?->flags->excluded === true) {
                continue;
            }

            $builder->setOffsetValueType(
                new ConstantStringType((string) $segment),
                $child?->type($view) ?? new MixedType(),
                $child === null || ! $child->isPresent($validated),
            );
        }

        if ($this->unsealed || ($view !== self::VALIDATED && $allowedKeys === null)) {
            $builder->makeUnsealed(new MixedType(), new MixedType());
        }

        return $builder->getArray();
    }

    private function wildcardShape(string $view): Type
    {
        $element = $this->children[self::WILDCARD];
        $isList  = $this->rule?->type->isList()->yes() === true;

        if ($view !== self::INPUT && $element->rule?->flags->excluded === true) {
            return new ConstantArrayType([], []);
        }

        $shape = new ArrayType($isList ? new IntegerType() : new MixedType(), $element->type($view));

        // Elements that validated() leaves out punch holes into the list.
        return $isList && ($view === self::INPUT || $element->isPresent(validated: true))
            ? TypeCombinator::intersect($shape, new AccessoryArrayListType())
            : $shape;
    }

    /** The type the node's own rule guarantees, ignoring nested rules. */
    private function leaf(): Type
    {
        return $this->nullable($this->rule?->resolveType() ?? new MixedType());
    }

    private function nullable(Type $type): Type
    {
        $flags = $this->rule?->flags;

        // A required nested attribute rules out a null parent.
        return $flags?->nullable === true && ! $flags->rejectsNull && ! $this->hasPresentChild(validated: false)
            ? TypeCombinator::addNull($type)
            : $type;
    }

    /** Whether the attribute is certainly there once its parent is. */
    private function isPresent(bool $validated): bool
    {
        $flags = $this->rule?->flags;

        if ($flags?->possiblyExcluded === true || $flags?->degraded === true) {
            return false;
        }

        // A parent assembled from nested attributes only appears once one of them does.
        if ($validated && ! $this->copiesInput(certainly: true)) {
            return $this->hasPresentChild(validated: true);
        }

        return ($flags?->required === true && ! $flags->possiblyUndefined) || $this->hasPresentChild(validated: false);
    }

    private function hasPresentChild(bool $validated): bool
    {
        foreach ($this->children as $segment => $child) {
            if ($segment !== self::WILDCARD && $child->isPresent($validated)) {
                return true;
            }
        }

        return false;
    }

    /**
     * validated() copies the input value whole when nothing below the attribute
     * remains to validate, or when its array rule is not the bare array/list rule.
     */
    private function copiesInput(bool $certainly): bool
    {
        $rule = $this->rule;

        if ($rule === null) {
            return false;
        }

        $prunes = $rule->flags->prunesUnvalidatedKeys;

        return $rule->isScalarOnly()
            || ($certainly ? $prunes === false : $prunes !== true)
            || $this->nestedRulesExcluded($certainly);
    }

    /** Whether an exclude rule removes every nested rule from the validator, or may do so unless $certainly. */
    private function nestedRulesExcluded(bool $certainly): bool
    {
        foreach ($this->children as $child) {
            $flags = $child->rule?->flags;

            if ($flags?->excluded === true || (! $certainly && $flags?->possiblyExcluded === true)) {
                continue;
            }

            // An absent optional attribute still leaves its rule in the validator.
            if ($flags !== null || ! $child->nestedRulesExcluded($certainly)) {
                return false;
            }
        }

        return true;
    }
}

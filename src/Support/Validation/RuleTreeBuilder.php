<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Support\Str;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_map;
use function array_pop;
use function array_reverse;
use function array_shift;
use function count;
use function ctype_digit;
use function implode;
use function is_string;
use function preg_split;
use function str_replace;

/**
 * Accumulates declarations from last to first, preserving raw paths until build().
 *
 * @internal
 *
 * @phpstan-type RuleTree array{nodes: array<string, RuleTreeNode>, unsealed: bool}
 */
final class RuleTreeBuilder
{
    /** @var array<string, array{path: list<string>, rule: ValidationRule}> Last declaration first. */
    private array $entries = [];

    private bool $unsealed = false;

    private bool $shadowed = false;

    /** Numeric appends are renumbered, so they cannot overwrite named declarations. */
    public function append(): void
    {
        $this->unsealed = true;
    }

    /** @param callable(): ValidationRule $readRule Decoded only if this declaration survives later writes. */
    public function add(Type $keyType, callable $readRule): void
    {
        $keyType = $keyType->toArrayKey();
        $keys    = $keyType->getConstantScalarValues();

        if (count($keys) !== 1 || ! is_string($keys[0])) {
            $this->uncertain($keyType);
            $this->shadowed = $this->shadowed || ! $keyType->isInteger()->yes();

            return;
        }

        $key = $keys[0];

        if ($this->shadowed || isset($this->entries[$key])) {
            return;
        }

        $this->entries[$key] = ['path' => self::segments($key), 'rule' => $readRule()];
    }

    public function unpack(Type $type): void
    {
        $spread = self::fromType($type);

        if (! $this->shadowed) {
            $this->entries += $spread->entries ?? [];
        }

        if ($spread !== null && ! $spread->unsealed) {
            return;
        }

        $keys = $type->getIterableKeyType();
        $this->uncertain($keys);
        $this->shadowed = $this->shadowed || ! $keys->isInteger()->yes();
    }

    public static function fromType(Type $type): self|null
    {
        if (! $type->isConstantArray()->yes()) {
            return null;
        }

        $alternatives = [];

        foreach ($type->getConstantArrays() as $array) {
            $builder = new self();

            foreach (array_reverse($array->getKeyTypes(), true) as $index => $key) {
                if ($array->isOptionalKey($index) || ! $key->toArrayKey()->isString()->yes()) {
                    $builder->unsealed = true;
                    continue;
                }

                $builder->add($key, static fn (): ValidationRule => ValidationRuleFactory::fromType($array->getValueTypes()[$index]) ?? ValidationRuleFactory::make([]));
            }

            if ($builder->unsealed) {
                $builder->uncertain($array->getIterableKeyType());
            }

            $alternatives[] = $builder;
        }

        return self::mergeAlternatives($alternatives);
    }

    /** @param list<self|null> $alternatives */
    public static function mergeAlternatives(array $alternatives): self|null
    {
        $merged = array_shift($alternatives);

        if ($merged === null) {
            return null;
        }

        $merged = clone $merged;

        foreach ($alternatives as $alternative) {
            if ($alternative === null) {
                return null;
            }

            $merged->unsealed = $merged->unsealed || $alternative->unsealed;
            $uncertain        = [];

            foreach ($merged->entries + $alternative->entries as $key => $entry) {
                $rule = isset($merged->entries[$key], $alternative->entries[$key])
                    ? $entry['rule']->merge($alternative->entries[$key]['rule']) : null;

                if ($rule === null) {
                    unset($merged->entries[$key]);
                    $uncertain[] = new ConstantStringType($key);
                } else {
                    $merged->entries[$key]['rule'] = $rule;
                }
            }

            if ($uncertain === []) {
                continue;
            }

            $merged->uncertain(TypeCombinator::union(...$uncertain));
        }

        return $merged;
    }

    /** Unknown ancestors invalidate descendants unless an explicit declaration protects that ancestor. */
    private function uncertain(Type $keys): void
    {
        $this->unsealed = true;
        $patterns       = [];

        foreach ($keys->getConstantStrings() as $pattern) {
            $value                                     = $pattern->getValue();
            $patterns[count(self::segments($value))][] = $value;
        }

        foreach ($this->entries as $key => $entry) {
            $path = $entry['path'];

            while (count($path) > 1) {
                array_pop($path);
                $ancestor = implode('.', $path);

                // Wildcards must match the ancestor's depth, not just its text.
                if (
                    ! isset($this->entries[$ancestor])
                    && (! $keys->isSuperTypeOf((new ConstantStringType($ancestor))->toArrayKey())->no()
                        || Str::is($patterns[count($path)] ?? [], $ancestor))
                ) {
                    unset($this->entries[$key]);
                    break;
                }
            }
        }
    }

    /**
     * Normalize paths only after uncertainty and alternatives have removed unsupported guarantees.
     *
     * @return RuleTree
     */
    public function build(): array
    {
        $roots = [];

        foreach (array_reverse($this->entries, true) as $entry) {
            $segments = array_map(
                static fn (string $segment): string => str_replace('\\.', '.', $segment),
                $entry['path'],
            );

            $name = array_shift($segments);
            $root = $roots[$name] ??= new RuleTreeNode();

            $node = $root;

            foreach ($segments as $segment) {
                // Numeric-looking child paths remain conservative, including zero-padded indices.
                if ($segment === '' || ctype_digit($segment) || (new ConstantStringType($segment))->toArrayKey()->isInteger()->yes()) {
                    $node->degraded = true;

                    continue 2;
                }

                $node->children[$segment] ??= new RuleTreeNode();

                if (isset($node->children[RuleTreeNode::WILDCARD]) && count($node->children) > 1) {
                    $node->degraded = true;
                }

                $node = $node->children[$segment];
            }

            $node->rule = $entry['rule'];
        }

        // A root wildcard can exclude or otherwise change every exact sibling field.
        return isset($roots[RuleTreeNode::WILDCARD])
            ? ['nodes' => [], 'unsealed' => true]
            : ['nodes' => $roots, 'unsealed' => $this->unsealed];
    }

    /** @return list<string> */
    private static function segments(string $key): array
    {
        return preg_split('/(?<!\\\\)\./', $key) ?: [$key];
    }
}

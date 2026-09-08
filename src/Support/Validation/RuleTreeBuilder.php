<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Support\Str;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_keys;
use function array_map;
use function array_pop;
use function array_replace;
use function array_shift;
use function count;
use function implode;
use function in_array;
use function preg_split;

/**
 * Collects the declarations of a rules() array in source order and keeps
 * the ones whose final rule is certain.
 *
 * @internal
 */
final class RuleTreeBuilder
{
    /** @var array<string, ValidationRule> Keyed by attribute path. */
    private array $rules = [];

    /** Keys that may have received a rule this builder cannot see. */
    private Type $uncertainKeys;

    private bool $unsealed = false;

    public function __construct()
    {
        $this->uncertainKeys = new NeverType();
    }

    public function declare(Type $key, ValidationRule $rule): void
    {
        $key     = $key->toArrayKey();
        $strings = $key->getConstantStrings();

        if (count($strings) === 1 && $key->equals($strings[0])) {
            $this->rules[$strings[0]->getValue()] = $rule;

            return;
        }

        $this->unsealed = true;
        $this->shadow($key);
    }

    /** A value without a key only adds a numeric attribute. */
    public function append(): void
    {
        $this->unsealed = true;
    }

    public function spread(Type $type): void
    {
        $spread = self::fromType($type);

        if ($spread === null) {
            $this->unsealed = true;
            $this->shadow($type->getIterableKeyType());

            return;
        }

        $this->unsealed = $this->unsealed || $spread->unsealed;
        $this->shadow($spread->uncertainKeys);
        $this->rules = array_replace($this->rules, $spread->rules);
    }

    public static function fromType(Type $type): self|null
    {
        if (! $type->isConstantArray()->yes()) {
            return null;
        }

        $alternatives = [];

        foreach ($type->getConstantArrays() as $array) {
            $builder = new self();

            foreach ($array->getKeyTypes() as $index => $key) {
                if ($array->isOptionalKey($index)) {
                    $builder->unsealed = true;
                    $builder->shadow($key);

                    continue;
                }

                $builder->declare($key, ValidationRuleFactory::fromType($array->getValueTypes()[$index]) ?? ValidationRuleFactory::make([]));
            }

            $alternatives[] = $builder;
        }

        return self::mergeAlternatives($alternatives);
    }

    /** @param list<self|null> $alternatives Rule sets of which exactly one applies. */
    public static function mergeAlternatives(array $alternatives): self|null
    {
        $merged = array_shift($alternatives);

        if ($merged === null || in_array(null, $alternatives, true)) {
            return null;
        }

        foreach ($alternatives as $alternative) {
            $merged->unsealed = $merged->unsealed || $alternative->unsealed;
            $merged->shadow($alternative->uncertainKeys);

            foreach (array_keys($merged->rules + $alternative->rules) as $key) {
                $rule = isset($merged->rules[$key], $alternative->rules[$key])
                    ? $merged->rules[$key]->merge($alternative->rules[$key])
                    : null;

                if ($rule === null) {
                    $merged->unsealed = true;
                    $merged->shadow(new ConstantStringType((string) $key));
                } else {
                    $merged->rules[$key] = $rule;
                }
            }
        }

        return $merged;
    }

    public function build(): RuleTree
    {
        // An unknown rule on an ancestor, such as exclude, would change the whole subtree.
        foreach (array_keys($this->rules) as $key) {
            $segments = self::segments((string) $key);

            while (count($segments) > 1) {
                array_pop($segments);
                $ancestor = implode('.', $segments);

                if (! isset($this->rules[$ancestor]) && $this->mayMatch($this->uncertainKeys, $ancestor)) {
                    unset($this->rules[$key]);
                    break;
                }
            }
        }

        return RuleTree::fromRules($this->rules, $this->unsealed);
    }

    /** Rules this builder cannot see may apply to $keys, so what was declared for them no longer holds. */
    private function shadow(Type $keys): void
    {
        $this->uncertainKeys = TypeCombinator::union($this->uncertainKeys, $keys);

        foreach (array_keys($this->rules) as $key) {
            if (! $this->mayMatch($keys, (string) $key)) {
                continue;
            }

            unset($this->rules[$key]);
        }
    }

    private function mayMatch(Type $keys, string $path): bool
    {
        $patterns = array_map(static fn (ConstantStringType $key): string => $key->getValue(), $keys->getConstantStrings());

        return ! $keys->isSuperTypeOf((new ConstantStringType($path))->toArrayKey())->no() || Str::is($patterns, $path);
    }

    /** @return list<string> */
    private static function segments(string $path): array
    {
        return preg_split('/(?<!\\\\)\./', $path) ?: [$path];
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_key_exists;
use function array_map;
use function array_values;
use function count;

/**
 * The fields of a request, nested the way their dotted names address the data.
 *
 * @internal
 */
final class RuleTree
{
    /** The rules of this field, or null when only nested fields have rules. */
    private Field|null $field = null;

    /** @var array<self> Nested fields by segment. `*` holds the rules of every element. */
    private array $children = [];

    /** @param array<string, Field> $fields */
    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $field) {
            $node = $this;

            foreach (RuleParser::segments($name) as $segment) {
                $node = $node->children[$segment] ??= new self();
            }

            $node->field = $field;
        }
    }

    /** The array shape of the whole data. An open shape may have fields that are not known. */
    public function shape(DataView $view, bool $open): Type
    {
        return $this->members($view, $open ? null : [])[0] ?? new ArrayType(new MixedType(), new MixedType());
    }

    /** @return array{Type, bool}|null The value and whether its key may be missing, or null when the key never exists. */
    private function resolve(DataView $view): array|null
    {
        $field    = $this->field;
        $excluded = $field->excluded ?? TrinaryLogic::createNo();

        if ($excluded->yes() && $view !== DataView::Input) {
            return null;
        }

        if ($field?->unknown === true || (! $excluded->no() && $view === DataView::Input)) {
            return [new MixedType(true), true];
        }

        $type     = $field?->valueType() ?? new MixedType();
        $optional = $field?->present !== true || ! $excluded->no();

        if ($this->children === [] && $field?->keys === null) {
            return [$type, $optional];
        }

        // Validated data holds only the validated children of an array that has rules of its own left below it.
        $pruned = match (true) {
            $view !== DataView::Validated => TrinaryLogic::createNo(),
            $field === null => TrinaryLogic::createYes(),
            default => $field->bare->and($this->validatesBelow()),
        };

        $results = [];

        if (! $pruned->no()) {
            $results[] = $this->nest($type, DataView::Validated, [], true);
        }

        if (! $pruned->yes()) {
            $results[] = $this->nest($type, $view === DataView::Input ? $view : DataView::Copied, $field->keys ?? null, $optional);
        }

        return [
            TypeCombinator::union(...array_map(static fn (array $result): Type => $result[0], $results)),
            // A field that may be excluded takes its nested fields with it.
            $results[0][1] || ($results[1][1] ?? false) || ! $excluded->no(),
        ];
    }

    /**
     * Describes the nested fields in the arrays of this field's own value.
     *
     * @param list<string>|null $keys The only keys the arrays may have.
     *
     * @return array{Type, bool}
     */
    private function nest(Type $type, DataView $view, array|null $keys, bool $optional): array
    {
        $arrays             = TypeCombinator::intersect($type, new ArrayType(new MixedType(), new MixedType()));
        [$shape, $required] = $this->members($view, $keys, $arrays->isList()->yes());

        // A field that is always present makes its parent an array holding it.
        if ($required) {
            return [$shape ?? $arrays, false];
        }

        if ($type instanceof MixedType) {
            return $this->field === null && $view === DataView::Validated ? [$shape ?? $arrays, true] : [$type, $optional];
        }

        if ($arrays instanceof NeverType) {
            return [$type, $optional];
        }

        return [TypeCombinator::union($shape ?? $arrays, TypeCombinator::remove($type, $arrays)), $optional];
    }

    /**
     * @param list<string>|null $keys The only keys the array may have, or null when it may have any.
     *
     * @return array{Type|null, bool} The array holding the nested fields, or null when they cannot be told apart,
     *                                and whether one of them is always present.
     */
    private function members(DataView $view, array|null $keys, bool $list = false): array
    {
        if (array_key_exists('*', $this->children)) {
            $element = $this->children['*']->resolve($view);
            // Laravel assembles scalar and empty-array elements before the others, so validated lists only keep their order without them.
            $ordered = $view === DataView::Input || $this->children['*']->keepsListOrder();
            $allowed = $this->field->keys ?? [];
            $shape   = match (true) {
                count($this->children) > 1 => null,
                // The only keys allowed are validated as elements.
                $allowed !== [] && $element !== null => $this->keyed($allowed, $element[0]),
                // Every element was excluded.
                $element === null => new ConstantArrayType([], []),
                // Elements without rules of their own say nothing about the array.
                $element[0] instanceof MixedType && ! $element[0]->isExplicitMixed() => null,
                $list && $ordered => TypeCombinator::intersect(new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $element[0]), new AccessoryArrayListType()),
                $list => new ArrayType(new IntegerType(), $element[0]),
                default => new ArrayType(new MixedType(), $element[0]),
            };

            return [$shape, false];
        }

        $builder  = ConstantArrayTypeBuilder::createEmpty();
        $required = false;

        foreach ($this->children as $segment => $child) {
            $member = $child->resolve($view);

            if ($member === null) {
                continue;
            }

            $builder->setOffsetValueType((new ConstantStringType((string) $segment))->toArrayKey(), $member[0], $member[1]);
            $required = $required || ! $member[1];
        }

        foreach ($keys ?? [] as $key) {
            if (isset($this->children[$key])) {
                continue;
            }

            $builder->setOffsetValueType((new ConstantStringType($key))->toArrayKey(), new MixedType(true), true);
        }

        if ($keys === null) {
            $builder->makeUnsealed(new MixedType(), new MixedType());
        }

        return [$builder->getArray(), $required];
    }

    /** @param list<string> $keys */
    private function keyed(array $keys, Type $value): Type
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($keys as $key) {
            $builder->setOffsetValueType((new ConstantStringType($key))->toArrayKey(), $value, true);
        }

        return $builder->getArray();
    }

    /** Whether the elements of a validated list are all scalars, or all hold a validated field. */
    private function keepsListOrder(): bool
    {
        if ($this->field !== null && ! $this->field->excluded->no()) {
            return false;
        }

        return $this->field?->valueType()->isArray()->no() === true
            || ($this->children !== [] && ! array_key_exists('*', $this->children) && $this->members(DataView::Validated, [])[1]);
    }

    /** Whether rules remain below this field once exclusion removed the excluded fields and their rules. */
    private function validatesBelow(): TrinaryLogic
    {
        return TrinaryLogic::createNo()->or(...array_map(
            static fn (self $child): TrinaryLogic => $child->field?->excluded->negate() ?? $child->validatesBelow(),
            array_values($this->children),
        ));
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Validation\Validator;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use ReflectionMethod;

use function array_filter;
use function class_exists;
use function count;
use function filter_var;
use function in_array;
use function is_int;
use function is_numeric;
use function max;
use function min;
use function str_contains;
use function version_compare;

use const FILTER_VALIDATE_INT;
use const LARAVEL_VERSION;

/**
 * The PHPStan type a validation rule constrains a value to, independent of what
 * the surrounding rule set says about the value's presence or nullability.
 *
 * @internal
 */
final class RuleTypes
{
    public const ARRAY_KEYS = 'Illuminate\\Validation\\Rules\\ArrayKeys';

    /** @param list<int|string> $parameters */
    public static function determineType(string $rule, array $parameters = []): Type|null
    {
        return match ($rule) {
            'array' => self::arrayType(),
            'array_keys' => class_exists(self::ARRAY_KEYS) ? self::arrayType() : null,
            'list' => TypeCombinator::intersect(
                new ArrayType(new IntegerType(), new MixedType()),
                new AccessoryArrayListType(),
            ),
            'lowercase' => TypeCombinator::intersect(new StringType(), new AccessoryLowercaseStringType()),
            'uppercase' => TypeCombinator::intersect(new StringType(), new AccessoryUppercaseStringType()),
            'active_url', 'alpha', 'ascii', 'hex_color', 'string', 'url', 'ulid', 'uuid' => new StringType(),
            'alpha_dash', 'alpha_num', 'doesnt_end_with', 'doesnt_start_with', 'ends_with', 'not_regex',
            'date_format', 'regex', 'starts_with' => TypeCombinator::union(new FloatType(), new IntegerType(), new StringType()),
            'email', 'ip', 'mac_address' => new StringType(),
            'json' => TypeCombinator::union(new BooleanType(), new FloatType(), new IntegerType(), new StringType()),
            'accepted' => TypeCombinator::union(
                new ConstantStringType('yes'),
                new ConstantStringType('on'),
                new ConstantIntegerType(1),
                new ConstantStringType('1'),
                new ConstantBooleanType(true),
                new ConstantStringType('true'),
            ),
            'declined' => TypeCombinator::union(
                new ConstantStringType('no'),
                new ConstantStringType('off'),
                new ConstantIntegerType(0),
                new ConstantStringType('0'),
                new ConstantBooleanType(false),
                new ConstantStringType('false'),
            ),
            'boolean' => in_array('strict', $parameters, true) && self::supportsStrictRule('validateBoolean')
                ? new BooleanType()
                : TypeCombinator::union(
                    new BooleanType(),
                    new ConstantIntegerType(1),
                    new ConstantIntegerType(0),
                    new ConstantStringType('1'),
                    new ConstantStringType('0'),
                ),
            'numeric' => in_array('strict', $parameters, true) && self::supportsStrictRule('validateNumeric')
                ? TypeCombinator::union(new FloatType(), new IntegerType())
                : self::numericType(),
            'decimal', 'digits', 'digits_between', 'max_digits', 'min_digits', 'multiple_of' => self::numericType(),
            'integer' => in_array('strict', $parameters, true) && self::supportsStrictRule('validateInteger')
                ? new IntegerType()
                : self::looseIntegerType(),
            default => null,
        };
    }

    /** @param list<string> $values */
    public static function determineInType(array $values, Type $baseType): Type
    {
        if ($baseType->equals(new MixedType())) {
            return self::inParameterType($values, $baseType) ?? $baseType;
        }

        if ($values === [] || (! $baseType->isString()->yes() && ! $baseType->equals(new IntegerType()))) {
            return $baseType;
        }

        $strictComparison = self::supportsStrictInComparison(LARAVEL_VERSION);
        $types            = [];

        foreach ($values as $value) {
            if ($baseType->equals(new IntegerType())) {
                $integer = filter_var($value, FILTER_VALIDATE_INT);

                // Validation preserves integers and compares their string representation.
                if ($integer === false || (string) $integer !== $value) {
                    return $baseType;
                }

                $types[] = new ConstantIntegerType($integer);
            } else {
                $types[] = ! $strictComparison && is_numeric($value)
                    ? self::numericStringType()
                    : new ConstantStringType($value);
            }
        }

        return TypeCombinator::union(...$types);
    }

    public static function inType(Type $valuesType, Type $baseType): Type|null
    {
        if (! $baseType->isArray()->yes() && ! $baseType->isString()->yes() && ! $baseType->equals(new MixedType())) {
            return null;
        }

        $multipleRepresentations = $baseType->isArray()->yes() || $baseType->equals(new MixedType());
        $constantArrays          = $valuesType->getConstantArrays();

        if (count($constantArrays) !== 1) {
            return null;
        }

        $types = [];

        foreach ($constantArrays[0]->getValueTypes() as $valueType) {
            $value = self::constantRuleString($valueType);

            if ($value === null) {
                return null;
            }

            if ($multipleRepresentations && ($value->getValue() === '' || is_numeric($value->getValue()))) {
                return null;
            }

            if (str_contains($value->getValue(), '\\') || str_contains($value->getValue(), '"')) {
                return null;
            }

            $types[] = is_numeric($value->getValue())
                ? new AccessoryNumericStringType()
                : $value;
        }

        if ($types === []) {
            return null;
        }

        return self::applyInType(TypeCombinator::union(...$types), $baseType);
    }

    /** @param list<string> $values */
    public static function inParameterType(array $values, Type $baseType): Type|null
    {
        $types = [];

        foreach ($values as $value) {
            if ($value === '' || is_numeric($value)) {
                return null;
            }

            $types[] = new ConstantStringType($value);
        }

        if ($types === []) {
            return null;
        }

        return self::applyInType(TypeCombinator::union(...$types), $baseType);
    }

    private static function applyInType(Type $type, Type $baseType): Type
    {
        if ($baseType->isList()->yes()) {
            return TypeCombinator::intersect(new ArrayType(new IntegerType(), $type), new AccessoryArrayListType());
        }

        if ($baseType->isArray()->yes()) {
            return new ArrayType(new MixedType(), $type);
        }

        return $type;
    }

    /**
     * @param list<int|null> $minimums
     * @param list<int|null> $maximums
     */
    public static function applyBounds(Type $type, array $minimums, array $maximums, bool $hasNumericRule): Type
    {
        $minimums = array_filter($minimums, static fn ($bound) => $bound !== null);
        $maximums = array_filter($maximums, static fn ($bound) => $bound !== null);
        $minimum  = $minimums === [] ? null : max($minimums);
        $maximum  = $maximums === [] ? null : min($maximums);

        if ($minimum === null && $maximum === null) {
            return $type;
        }

        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            return $type;
        }

        if ((new IntegerType())->isSuperTypeOf($type)->yes()) {
            $refinedType = TypeCombinator::intersect($type, IntegerRangeType::fromInterval($minimum, $maximum));

            return $refinedType instanceof NeverType ? $type : $refinedType;
        }

        if ($hasNumericRule) {
            $range = IntegerRangeType::fromInterval($minimum, $maximum);

            return $type->traverse(static fn (Type $innerType): Type => $innerType->isInteger()->yes()
                ? TypeCombinator::intersect($innerType, $range)
                : $innerType);
        }

        if ($minimum === null || $minimum <= 0) {
            return $type;
        }

        if ($type->isString()->yes()) {
            return TypeCombinator::intersect($type, new AccessoryNonEmptyStringType());
        }

        if (! $type->isArray()->yes()) {
            return $type;
        }

        return TypeCombinator::intersect($type, new NonEmptyArrayType());
    }

    public static function enumType(Type $classStringType): Type|null
    {
        $cases = $classStringType->getClassStringObjectType()->getEnumCases();

        if ($cases === []) {
            return null;
        }

        $types             = [];
        $hasIntegerBacking = false;

        foreach ($cases as $case) {
            $backingValueType = $case->getBackingValueType();

            if ($backingValueType === null) {
                $types[] = $case;

                continue;
            }

            $types[] = $backingValueType;

            if (! $backingValueType->isInteger()->yes()) {
                continue;
            }

            $types[]           = self::numericStringType();
            $hasIntegerBacking = true;
        }

        $type = TypeCombinator::union(...$types);

        return $hasIntegerBacking ? TypeUtils::toBenevolentUnion($type) : $type;
    }

    /**
     * @param list<string|null> $values
     *
     * @return list<ConstantIntegerType|ConstantStringType>|null
     */
    public static function arrayKeyTypes(array $values): array|null
    {
        $keys = [];

        foreach ($values as $value) {
            $keyType         = (new ConstantStringType((string) $value))->toArrayKey();
            $constantStrings = $keyType->getConstantStrings();

            if (count($constantStrings) === 1) {
                $keys[] = $constantStrings[0];

                continue;
            }

            $values = $keyType->getConstantScalarValues();

            if (count($values) !== 1 || ! is_int($values[0])) {
                return null;
            }

            $keys[] = new ConstantIntegerType($values[0]);
        }

        return $keys;
    }

    public static function constantRuleString(Type $type): ConstantStringType|null
    {
        $values = $type->getConstantScalarValues();

        if ($type->isConstantScalarValue()->yes() && count($values) === 1) {
            return new ConstantStringType((string) $values[0]);
        }

        $cases = $type->getEnumCases();

        if (! $type->isEnum()->yes() || count($cases) !== 1) {
            return null;
        }

        $backingValueType = $cases[0]->getBackingValueType();

        if ($backingValueType === null) {
            return new ConstantStringType($cases[0]->getEnumCaseName());
        }

        $values = $backingValueType->getConstantScalarValues();

        return count($values) === 1
            ? new ConstantStringType((string) $values[0])
            : null;
    }

    public static function intersectConstraint(Type|null $constraintType, Type $type): Type
    {
        return $constraintType === null
            ? $type
            : TypeCombinator::intersect($constraintType, $type);
    }

    public static function arrayType(): Type
    {
        return new ArrayType(new MixedType(), new MixedType());
    }

    private static function numericType(): Type
    {
        return TypeUtils::toBenevolentUnion(TypeCombinator::union(
            new FloatType(),
            new IntegerType(),
            self::numericStringType(),
        ));
    }

    private static function looseIntegerType(): Type
    {
        return TypeUtils::toBenevolentUnion(TypeCombinator::union(self::numericType(), new ConstantBooleanType(true)));
    }

    private static function numericStringType(): Type
    {
        return TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType());
    }

    private static function supportsStrictRule(string $method): bool
    {
        return (new ReflectionMethod(Validator::class, $method))->getNumberOfParameters() >= 3;
    }

    private static function supportsStrictInComparison(string $version): bool
    {
        return version_compare($version, '12.67.0', '>=')
            && (version_compare($version, '13.0.0', '<') || version_compare($version, '13.26.0', '>='));
    }
}

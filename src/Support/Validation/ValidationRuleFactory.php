<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Numeric;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use ReflectionMethod;

use function array_filter;
use function count;
use function explode;
use function filter_var;
use function implode;
use function in_array;
use function is_int;
use function is_numeric;
use function is_string;
use function str_contains;
use function str_getcsv;

use const FILTER_VALIDATE_INT;

/** @internal */
final class ValidationRuleFactory
{
    private const ANY_OF = 'Illuminate\\Validation\\Rules\\AnyOf';

    private const ARRAY_KEYS = 'Illuminate\\Validation\\Rules\\ArrayKeys';

    private const STRING_RULE = 'Illuminate\\Validation\\Rules\\StringRule';

    /** @param string|array<string|Type> $rules */
    public static function make(string|array $rules): ValidationRule
    {
        $possiblyUndefined = false;
        $nullable          = false;
        $required          = false;
        $rejectsNull       = false;

        $type     = null;
        $inValues = null;
        $min      = null;
        $max      = null;

        $constraintType  = null;
        $allowedKeys     = null;
        $anyOfRuleGroups = [];

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $ruleObjects = array_filter($rules, static fn ($rule) => ! is_string($rule));

        foreach ($ruleObjects as $rule) {
            if ((new ObjectType(self::ANY_OF))->isSuperTypeOf($rule)->yes()) {
                $alternatives = self::anyOfAlternatives($rule->getTemplateType(self::ANY_OF, 'TRules'));

                if ($alternatives !== null) {
                    $anyOfRuleGroups[] = $alternatives;
                }
            } elseif ((new ObjectType(self::ARRAY_KEYS))->isSuperTypeOf($rule)->yes()) {
                $type        = self::arrayType();
                $allowedKeys = self::constantArrayKeys($rule->getTemplateType(self::ARRAY_KEYS, 'TKeys'));
            } elseif ((new ObjectType(ArrayRule::class))->isSuperTypeOf($rule)->yes()) {
                $type        = self::arrayType();
                $allowedKeys = self::constantArrayKeys($rule->getTemplateType(ArrayRule::class, 'TKeys'));
            } elseif ((new ObjectType('Illuminate\\Validation\\Rules\\Contains'))->isSuperTypeOf($rule)->yes()) {
                $type = self::arrayType();
            } elseif ((new ObjectType('Illuminate\\Validation\\Rules\\DoesntContain'))->isSuperTypeOf($rule)->yes()) {
                $type = self::arrayType();
            } elseif ((new ObjectType(Date::class))->isSuperTypeOf($rule)->yes()) {
                $dateType       = $rule->getTemplateType(Date::class, 'TValue');
                $constraintType = $constraintType === null
                    ? $dateType
                    : TypeCombinator::intersect($constraintType, $dateType);
            } elseif ((new ObjectType(Email::class))->isSuperTypeOf($rule)->yes()) {
                $type = new StringType();
            } elseif ((new ObjectType(Enum::class))->isSuperTypeOf($rule)->yes()) {
                $enumType = self::enumType($rule->getTemplateType(Enum::class, 'TEnum'));

                if ($enumType !== null) {
                    $constraintType = $constraintType === null
                        ? $enumType
                        : TypeCombinator::intersect($constraintType, $enumType);
                }
            } elseif ((new ObjectType(Numeric::class))->isSuperTypeOf($rule)->yes()) {
                $numericType    = $rule->getTemplateType(Numeric::class, 'TValue');
                $constraintType = $constraintType === null
                    ? $numericType
                    : TypeCombinator::intersect($constraintType, $numericType);
            } elseif ((new ObjectType(self::STRING_RULE))->isSuperTypeOf($rule)->yes()) {
                $type           = new StringType();
                $stringType     = $rule->getTemplateType(self::STRING_RULE, 'TValue');
                $constraintType = $constraintType === null
                    ? $stringType
                    : TypeCombinator::intersect($constraintType, $stringType);
            } elseif ((new ObjectType(Dimensions::class))->isSuperTypeOf($rule)->yes()) {
                $type = new ObjectType(UploadedFile::class);
            } elseif ((new ObjectType(FileRule::class))->isSuperTypeOf($rule)->yes()) {
                $type = new ObjectType(UploadedFile::class);
            } elseif ((new ObjectType(Password::class))->isSuperTypeOf($rule)->yes()) {
                $type = new StringType();
            }
        }

        $rules = array_filter($rules, static fn ($rule) => is_string($rule) && $rule !== '');

        foreach ($rules as $rule) {
            $parameters = [];

            if (str_contains($rule, ':')) {
                [$rule, $parameters] = explode(':', $rule, 2);
                $parameters          = explode(',', $parameters);
            }

            if ($rule === 'in') {
                $inValues = $parameters;

                continue;
            }

            if ($rule === 'nullable') {
                $nullable = true;
            }

            if ($rule === 'sometimes') {
                $possiblyUndefined = true;
            }

            // `present` guarantees the key exists just like `required`; it only
            // additionally allows the value to be empty, which doesn't affect the type.
            if ($rule === 'required') {
                $rejectsNull = true;
                $required    = true;
            }

            if ($rule === 'present') {
                $required = true;
            }

            if ($rule === 'min') {
                $min = self::intParameter($parameters, 0);
            }

            if ($rule === 'max') {
                $max = self::intParameter($parameters, 0);
            }

            if ($rule === 'between') {
                $min = self::intParameter($parameters, 0);
                $max = self::intParameter($parameters, 1);
            }

            $determinedType = self::determineType($rule, $parameters);

            if ($determinedType === null) {
                continue;
            }

            $type = $type === null
                ? $determinedType
                : TypeCombinator::intersect($type, $determinedType);
        }

        if ($inValues !== null) {
            if ($type?->isArray()->yes()) {
                $inType = self::inParameterType($inValues, $type);

                if ($inType !== null) {
                    $constraintType = $constraintType === null
                        ? $inType
                        : TypeCombinator::intersect($constraintType, $inType);
                }
            } else {
                $type = self::determineInType($inValues, $type ?? new MixedType());
            }
        }

        // `min`/`max`/`between` constrain the value only for integers (for strings they
        // constrain the length, for arrays the count).
        if ($type?->equals(new IntegerType()) && ($min !== null || $max !== null) && ($min === null || $max === null || $min <= $max)) {
            $type = IntegerRangeType::fromInterval($min, $max);
        }

        $type ??= new MixedType(true);

        foreach ($ruleObjects as $rule) {
            if (! (new ObjectType(In::class))->isSuperTypeOf($rule)->yes()) {
                continue;
            }

            $inType = self::inType($rule->getTemplateType(In::class, 'TValues'), $type);

            if ($inType === null) {
                continue;
            }

            $constraintType = $constraintType === null
                ? $inType
                : TypeCombinator::intersect($constraintType, $inType);
        }

        return new ValidationRule(
            $type,
            $nullable,
            $possiblyUndefined,
            $required,
            $constraintType,
            $allowedKeys,
            $anyOfRuleGroups,
            $rejectsNull,
        );
    }

    /** @return list<ValidationRule>|null */
    private static function anyOfAlternatives(Type $type): array|null
    {
        // Laravel also lets non-implicit list alternatives pass associative input because it validates that input
        // directly. Including `array` in every inferred AnyOf type would erase most useful narrowing.
        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1 || ! $constantArrays[0]->isList()->yes()) {
            return null;
        }

        $alternatives = [];

        foreach ($constantArrays[0]->getValueTypes() as $alternativeType) {
            $alternative = self::fromType($alternativeType, requireList: true);

            if ($alternative === null) {
                return null;
            }

            $alternatives[] = $alternative;
        }

        return $alternatives === [] ? null : $alternatives;
    }

    public static function fromType(Type $type, bool $requireList = false): ValidationRule|null
    {
        $strings = $type->getConstantStrings();

        if (count($strings) === 1) {
            return self::make($strings[0]->getValue());
        }

        if ($type->isObject()->yes()) {
            return self::make([$type]);
        }

        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1 || ($requireList && ! $constantArrays[0]->isList()->yes())) {
            return null;
        }

        $rules = [];

        foreach ($constantArrays[0]->getValueTypes() as $ruleType) {
            $strings = $ruleType->getConstantStrings();

            if (count($strings) === 1) {
                $rules[] = $strings[0]->getValue();
            } elseif ($ruleType->isObject()->yes()) {
                $rules[] = $ruleType;
            } else {
                return self::make([]);
            }
        }

        return self::make($rules);
    }

    /** @return list<ConstantIntegerType|ConstantStringType>|null */
    private static function constantArrayKeys(Type $type): array|null
    {
        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1 || $constantArrays[0]->getValueTypes() === []) {
            return null;
        }

        $serializedKeys = [];

        foreach ($constantArrays[0]->getValueTypes() as $valueType) {
            $value = self::constantRuleString($valueType);

            if ($value === null) {
                return null;
            }

            $serializedKeys[] = $value->getValue();
        }

        $keys = [];

        foreach (str_getcsv(implode(',', $serializedKeys), escape: '\\') as $value) {
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

    private static function enumType(Type $classStringType): Type|null
    {
        $cases = $classStringType->getClassStringObjectType()->getEnumCases();

        if ($cases === []) {
            return null;
        }

        $types = [];

        foreach ($cases as $case) {
            $backingValueType = $case->getBackingValueType();

            if ($backingValueType === null) {
                $types[] = $case;

                continue;
            }

            $types[] = $backingValueType;

            foreach ($backingValueType->getConstantScalarValues() as $value) {
                if (! is_int($value)) {
                    continue;
                }

                $types[] = new ConstantStringType((string) $value);
            }
        }

        return TypeCombinator::union(...$types);
    }

    private static function inType(Type $valuesType, Type $baseType): Type|null
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
    private static function inParameterType(array $values, Type $baseType): Type|null
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

    private static function constantRuleString(Type $type): ConstantStringType|null
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

    /** @param list<int|string> $parameters */
    private static function determineType(string $rule, array $parameters = []): Type|null
    {
        return match ($rule) {
            'array' => self::arrayType(),
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

    /** @param list<int|string> $parameters */
    private static function intParameter(array $parameters, int $index): int|null
    {
        $value = filter_var($parameters[$index] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }

    /** @param list<string> $values */
    private static function determineInType(array $values, Type $baseType): Type
    {
        if ($baseType->equals(self::looseIntegerType())) {
            return $baseType;
        }

        $types = [];

        foreach ($values as $value) {
            if ($baseType->equals(new IntegerType()) && is_numeric($value)) {
                $integer = filter_var($value, FILTER_VALIDATE_INT);
                $types[] = $integer === false
                    ? new ConstantFloatType((float) $value)
                    : new ConstantIntegerType($integer);
            } else {
                $types[] = new ConstantStringType($value);
            }
        }

        return TypeCombinator::union(...$types);
    }

    private static function arrayType(): Type
    {
        return new ArrayType(new MixedType(), new MixedType());
    }

    private static function looseIntegerType(): Type
    {
        return TypeUtils::toBenevolentUnion(TypeCombinator::union(new IntegerType(), self::numericStringType()));
    }

    private static function numericType(): Type
    {
        return TypeUtils::toBenevolentUnion(TypeCombinator::union(
            new FloatType(),
            new IntegerType(),
            self::numericStringType(),
        ));
    }

    private static function numericStringType(): Type
    {
        return TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType());
    }

    private static function supportsStrictRule(string $method): bool
    {
        return (new ReflectionMethod(Validator::class, $method))->getNumberOfParameters() >= 3;
    }
}

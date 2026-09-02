<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Numeric;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionMethod;

use function array_filter;
use function array_unique;
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
use function str_replace;

use const FILTER_VALIDATE_INT;

/** @internal */
final class ValidationRuleFactory
{
    private const ARRAY_KEYS = 'Illuminate\\Validation\\Rules\\ArrayKeys';

    private const STRING_RULE = 'Illuminate\\Validation\\Rules\\StringRule';

    private const LOOSE_INTEGER_TYPE = 'int|numeric-string';

    private const NUMERIC_TYPE = 'float|int|numeric-string';

    /** @param string|array<string|Type> $rules */
    public static function make(string|array $rules): ValidationRule
    {
        $possiblyUndefined = false;
        $nullable          = false;
        $required          = false;
        $benevolent        = false;

        $type     = '';
        $inValues = null;
        $min      = null;
        $max      = null;

        $constraintType = null;
        $allowedKeys    = null;

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $ruleObjects = array_filter($rules, static fn ($rule) => ! is_string($rule));

        foreach ($ruleObjects as $rule) {
            if ((new ObjectType(self::ARRAY_KEYS))->isSuperTypeOf($rule)->yes()) {
                $type        = 'array';
                $allowedKeys = self::constantArrayKeys($rule->getTemplateType(self::ARRAY_KEYS, 'TKeys'));
            } elseif ((new ObjectType(ArrayRule::class))->isSuperTypeOf($rule)->yes()) {
                $type        = 'array';
                $allowedKeys = self::constantArrayKeys($rule->getTemplateType(ArrayRule::class, 'TKeys'));
            } elseif ((new ObjectType('Illuminate\\Validation\\Rules\\Contains'))->isSuperTypeOf($rule)->yes()) {
                $type = 'array';
            } elseif ((new ObjectType(Date::class))->isSuperTypeOf($rule)->yes()) {
                $dateType       = $rule->getTemplateType(Date::class, 'TValue');
                $constraintType = $constraintType === null
                    ? $dateType
                    : TypeCombinator::intersect($constraintType, $dateType);
            } elseif ((new ObjectType(Email::class))->isSuperTypeOf($rule)->yes()) {
                $type = 'string|Stringable';
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
                $type           = 'string';
                $stringType     = $rule->getTemplateType(self::STRING_RULE, 'TValue');
                $constraintType = $constraintType === null
                    ? $stringType
                    : TypeCombinator::intersect($constraintType, $stringType);
            } elseif ((new ObjectType(FileRule::class))->isSuperTypeOf($rule)->yes()) {
                $type = UploadedFile::class;
            } elseif ((new ObjectType(Password::class))->isSuperTypeOf($rule)->yes()) {
                $type = 'string';
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

            if (self::isUtility($rule)) {
                if ($rule === 'nullable') {
                    $nullable = true;
                }

                if ($rule === 'sometimes') {
                    $possiblyUndefined = true;
                }

                // `present` guarantees the key exists just like `required`; it only
                // additionally allows the value to be empty, which doesn't affect the type.
                if ($rule === 'required' || $rule === 'present') {
                    $required = true;
                }

                if ($rule === 'min') {
                    $min = self::intParameter($parameters, 0);
                }

                if ($rule === 'max') {
                    $max = self::intParameter($parameters, 0);
                }

                continue;
            }

            if ($rule === 'between') {
                $min = self::intParameter($parameters, 0);
                $max = self::intParameter($parameters, 1);
            }

            $determinedType = self::determineType($rule, $parameters);

            if ($determinedType === null) {
                continue;
            }

            $type       = $determinedType;
            $benevolent = $determinedType === self::NUMERIC_TYPE || $determinedType === self::LOOSE_INTEGER_TYPE;
        }

        if ($inValues !== null) {
            $type = self::determineInType($inValues, $type);
        }

        // `min`/`max`/`between` constrain the value only for integers (for strings they
        // constrain the length, for arrays the count).
        if ($type === 'int' && ($min !== null || $max !== null) && ($min === null || $max === null || $min <= $max)) {
            $type = 'int<' . ($min ?? 'min') . ', ' . ($max ?? 'max') . '>';
        }

        if ($type === '') {
            $type = 'mixed';
        }

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
            implode('|', $rules),
            $type,
            $nullable,
            $possiblyUndefined,
            $required,
            $benevolent,
            $constraintType,
            $allowedKeys,
        );
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

    private static function inType(Type $valuesType, string $baseType): Type|null
    {
        if ($baseType !== 'string' && $baseType !== 'lowercase-string' && $baseType !== 'uppercase-string') {
            return null;
        }

        $constantArrays = $valuesType->getConstantArrays();

        if (count($constantArrays) !== 1) {
            return null;
        }

        $types = [];

        foreach ($constantArrays[0]->getValueTypes() as $valueType) {
            $value = self::constantRuleString($valueType);

            if ($value === null) {
                return null;
            }

            if (str_contains($value->getValue(), '\\') || str_contains($value->getValue(), '"')) {
                return null;
            }

            $types[] = is_numeric($value->getValue())
                ? new AccessoryNumericStringType()
                : $value;
        }

        return $types === [] ? null : TypeCombinator::union(...$types);
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

    private static function isUtility(string $rule): bool
    {
        return in_array($rule, [
            'anyOf',
            'bail',
            'exclude',
            'exclude_if',
            'exclude_unless',
            'exclude_with',
            'exclude_without',
            'filled',
            'max',
            'min',
            'missing',
            'missing_if',
            'missing_unless',
            'missing_with',
            'missing_with_all',
            'nullable',
            'present',
            'present_if',
            'present_unless',
            'present_with',
            'present_with_all',
            'prohibited',
            'prohibited_if',
            'prohibited_if_accepted',
            'prohibited_if_declined',
            'prohibited_unless',
            'prohibits',
            'required',
            'required_if',
            'required_if_accepted',
            'required_if_declined',
            'required_unless',
            'required_with',
            'required_with_all',
            'required_without',
            'required_without_all',
            'required_array_keys',
            'sometimes',
        ], true);
    }

    /** @param list<int|string> $parameters */
    private static function determineType(string $rule, array $parameters = []): string|null
    {
        return match ($rule) {
            'array', 'list' => $rule,
            'lowercase' => 'lowercase-string',
            'uppercase' => 'uppercase-string',
            'active_url', 'alpha', 'ascii', 'hex_color', 'string', 'url', 'ulid', 'uuid' => 'string',
            'alpha_dash', 'alpha_num', 'doesnt_end_with', 'doesnt_start_with', 'ends_with', 'not_regex',
            'regex', 'starts_with' => 'float|int|string',
            'email', 'ip', 'mac_address' => 'string|Stringable',
            'json' => 'bool|float|int|string|Stringable',
            'accepted' => "'yes'|'on'|1|'1'|true|'true'",
            'declined' => "'no'|'off'|0|'0'|false|'false'",
            'boolean' => in_array('strict', $parameters, true) && self::supportsStrictRule('validateBoolean')
                ? 'bool'
                : "true|false|1|0|'1'|'0'",
            'numeric' => in_array('strict', $parameters, true) && self::supportsStrictRule('validateNumeric')
                ? 'float|int'
                : self::NUMERIC_TYPE,
            'decimal', 'digits', 'digits_between', 'max_digits', 'min_digits', 'multiple_of' => self::NUMERIC_TYPE,
            'integer' => in_array('strict', $parameters, true) && self::supportsStrictRule('validateInteger')
                ? 'int'
                : self::LOOSE_INTEGER_TYPE,
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
    private static function determineInType(array $values, string $baseType): string
    {
        if ($baseType === self::LOOSE_INTEGER_TYPE) {
            return $baseType;
        }

        $literals = [];

        foreach ($values as $value) {
            if ($baseType === 'int' && is_numeric($value)) {
                $literals[] = $value;
            } else {
                $literals[] = "'" . str_replace("'", "\\'", $value) . "'";
            }
        }

        return implode('|', array_unique($literals));
    }

    private static function supportsStrictRule(string $method): bool
    {
        return (new ReflectionMethod(Validator::class, $method))->getNumberOfParameters() >= 3;
    }
}

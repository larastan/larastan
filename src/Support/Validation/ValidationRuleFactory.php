<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Validation\Validator;
use ReflectionMethod;

use function array_filter;
use function array_unique;
use function explode;
use function filter_var;
use function implode;
use function in_array;
use function is_numeric;
use function is_string;
use function str_contains;
use function str_replace;

use const FILTER_VALIDATE_INT;

/** @internal */
final class ValidationRuleFactory
{
    private const LOOSE_INTEGER_TYPE = 'float|int|numeric-string|true|Stringable';

    private const NUMERIC_TYPE = 'float|int|numeric-string';

    /** @param string|mixed[] $rules */
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

        if (is_string($rules)) {
            $rules = explode('|', $rules);
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
            $benevolent = $determinedType === self::NUMERIC_TYPE;
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

        return new ValidationRule(implode('|', $rules), $type, $nullable, $possiblyUndefined, $required, $benevolent);
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

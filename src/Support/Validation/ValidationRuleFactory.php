<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use function array_filter;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function str_contains;

/** @internal */
final class ValidationRuleFactory
{
    /** @param string|string[] $rules */
    public static function make(string|array $rules): ValidationRule
    {
        $possiblyUndefined = false;
        $nullable          = false;

        $type = '';

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $rules = array_filter($rules);

        foreach ($rules as $rule) {
            $parameters = [];

            if (str_contains($rule, ':')) {
                [$rule, $parameters] = explode(':', $rule, 2);
                $parameters          = explode(',', $parameters);
            }

            if (self::isUtility($rule)) {
                if ($rule === 'nullable') {
                    $nullable = true;
                }

                if ($rule === 'sometimes') {
                    $possiblyUndefined = true;
                }

                continue;
            }

            $type = self::determineType($rule);
        }

        if ($type === '') {
            // write to a file to debug later with $rules array and rulenames that are not utility, string or boolean or integer
             file_put_contents(__DIR__ . '/debug.txt', implode('|', $rules) . PHP_EOL, FILE_APPEND);
             $type = 'mixed';
        }

        return new ValidationRule(implode('|', $rules), $type, $nullable, $possiblyUndefined);
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

    public static function isString(string $rule): bool
    {
        return in_array($rule, [
            'active_url',
            'alpha',
            'alpha_dash',
            'alpha_num',
            'ascii',
            'confirmed',
            'current_password',
            'different',
            'doesnt_start_with',
            'doesnt_end_with',
            'email',
            'ends_with',
            'enum',
            'hex_color',
            'in',
            'ip',
            'json',
            'lowercase',
            'mac_address',
            'not_in',
            'regex',
            'not_regex',
            'same',
            'size',
            'starts_with',
            'string',
            'uppercase',
            'url',
            'ulid',
            'uuid',
        ], true);
    }

    public static function isBoolean(string $rule): bool
    {
        return in_array($rule, [
            'accepted',
            'accepted_if',
            'boolean',
            'declined',
            'declined_if',
        ], true);
    }

    public static function isInteger(string $rule): bool
    {
        return in_array($rule, [
            'between',
            'decimal',
            'different',
            'digits',
            'digits_between',
            'gt',
            'gte',
            'integer',
            'lt',
            'lte',
            'max_digits',
            'min_digits',
            'multiple_of',
            'numeric',
            'same',
        ], true);
    }

    /** @param list<int|string> $parameters */
    private static function determineType(string $rule, array $parameters = []): string
    {
        if (self::isString($rule)) {
            return match ($rule) {
                'lowercase' => 'lowercase-string',
                'uppercase' => 'uppercase-string',
                default => 'string',
            };
        }

        if (self::isBoolean($rule)) {
            return match ($rule) {
                'accepted', 'accepted_if' => "'yes'|'on'|1|'1'|true|'true'",
                'declined', 'declined_if' => "'no'|'off'|0|'0'|false|'false'",
                'boolean' => in_array('strict', $parameters, true) ? 'bool' : "true|false|1|0|'1'|'0'",
                default => 'bool',
            };
        }

        if (self::isInteger($rule)) {
            return 'int';
        }

        return 'mixed';
    }
}

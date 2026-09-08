<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationRuleParser;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_filter;
use function array_map;
use function array_values;
use function class_exists;
use function count;
use function explode;
use function filter_var;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function str_getcsv;

use const FILTER_VALIDATE_INT;

/** @internal */
final class ValidationRuleFactory
{
    private const ANY_OF            = 'Illuminate\\Validation\\Rules\\AnyOf';
    private const STRING_RULE       = 'Illuminate\\Validation\\Rules\\StringRule';
    private const CONDITIONAL_RULES = 'Illuminate\\Validation\\ConditionalRules';

    /** @param string|array<string|Type|array{string, null}> $rules */
    public static function make(string|array $rules): ValidationRule
    {
        $rules      = is_string($rules) ? explode('|', $rules) : $rules;
        $objects    = array_filter($rules, static fn ($rule) => $rule instanceof Type);
        $objectRule = self::fromObjectRules($objects);
        $type       = $objectRule->type;
        $constraint = $objectRule->constraintType;
        $keys       = $objectRule->allowedKeys;
        $names      = [];
        $minimums   = [];
        $maximums   = [];
        $inValues   = null;

        foreach ($rules as $rawRule) {
            if ($rawRule instanceof Type || $rawRule === '') {
                continue;
            }

            [$name, $parameters] = ValidationRuleParser::parse($rawRule);
            $name                = Str::snake($name);
            $names[$name]        = true;
            $parameters          = is_array($rawRule) ? null : array_values(array_filter($parameters, is_string(...)));

            if ($name === 'in') {
                $inValues = $parameters ?? $inValues;
            }

            if ($parameters !== null && $parameters !== [] && ($name === 'array' || ($name === 'array_keys' && class_exists(RuleTypes::ARRAY_KEYS)))) {
                $keys = RuleTypes::arrayKeyTypes($parameters);
            }

            if (in_array($name, ['min', 'between', 'size'], true)) {
                $minimums[] = self::intParameter($parameters, 0);
            }

            if (in_array($name, ['max', 'between', 'size'], true)) {
                $maximums[] = self::intParameter($parameters, $name === 'between' ? 1 : 0);
            }

            $determinedType = RuleTypes::determineType($name, $parameters ?? []);

            if ($determinedType === null) {
                continue;
            }

            $type = TypeCombinator::intersect($type, $determinedType);
        }

        // Membership needs the base type; object In rules additionally see the bounds.
        if ($inValues !== null) {
            if ($type->isArray()->yes()) {
                $inType = RuleTypes::inParameterType($inValues, $type);

                if ($inType !== null) {
                    $constraint = RuleTypes::intersectConstraint($constraint, $inType);
                }
            } else {
                $type = RuleTypes::determineInType($inValues, $type);
            }
        }

        $type = RuleTypes::applyBounds($type, $minimums, $maximums, isset($names['integer']) || isset($names['numeric']) || isset($names['decimal']));

        foreach ($objects as $rule) {
            if (! self::isObjectRule($rule, Rules\In::class)) {
                continue;
            }

            $inType = RuleTypes::inType($rule->getTemplateType(Rules\In::class, 'TValues'), $type);

            if ($inType === null) {
                continue;
            }

            $constraint = RuleTypes::intersectConstraint($constraint, $inType);
        }

        $rejectsNull = isset($names['required']) || isset($names['accepted']) || isset($names['declined']);

        return new ValidationRule(
            type: $type,
            constraintType: $constraint,
            allowedKeys: $keys,
            anyOfRuleGroups: $objectRule->anyOfRuleGroups,
            flags: $objectRule->flags->both(new RuleFlags(
                nullable: isset($names['nullable']),
                possiblyUndefined: isset($names['sometimes']),
                required: $rejectsNull || isset($names['present']),
                rejectsNull: $rejectsNull,
                possiblyExcluded: isset($names['exclude']) || isset($names['exclude_if']) || isset($names['exclude_unless'])
                    || isset($names['exclude_with']) || isset($names['exclude_without']),
                excluded: isset($names['exclude']),
                // Laravel distinguishes bare rules from parameterized or normalized spellings.
                prunesUnvalidatedKeys: in_array('array', $rules, true) || in_array('list', $rules, true),
            )),
        );
    }

    /** @param array<Type> $rules */
    private static function fromObjectRules(array $rules): ValidationRule
    {
        $type       = new MixedType(true);
        $constraint = null;
        $keys       = null;
        $groups     = [];
        $flags      = new RuleFlags();

        foreach ($rules as $rule) {
            switch (true) {
                case self::isObjectRule($rule, self::CONDITIONAL_RULES):
                case self::isObjectRule($rule, self::ANY_OF):
                    $alternatives = self::alternatives($rule, ! self::isObjectRule($rule, self::CONDITIONAL_RULES));
                    $groups       = [...$groups, ...$alternatives->anyOfRuleGroups];
                    $flags        = $flags->both($alternatives->flags);
                    break;
                case self::isObjectRule($rule, RuleTypes::ARRAY_KEYS):
                    $type = RuleTypes::arrayType();
                    $keys = self::constantArrayKeys(self::template($rule, RuleTypes::ARRAY_KEYS, 'TKeys'));
                    break;
                case self::isObjectRule($rule, Rules\ArrayRule::class):
                    $keysType = $rule->getTemplateType(Rules\ArrayRule::class, 'TKeys');
                    $type     = RuleTypes::arrayType();
                    $keys     = self::constantArrayKeys($keysType);
                    $flags    = $flags->both(new RuleFlags(
                        prunesUnvalidatedKeys: $keysType->isNull()->yes() || ($keysType->isArray()->yes() && $keysType->isIterableAtLeastOnce()->no())
                            ? true
                            : ($keysType->isArray()->yes() && $keysType->isIterableAtLeastOnce()->yes() ? false : null),
                    ));
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\Contains'):
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\DoesntContain'):
                    $type = RuleTypes::arrayType();
                    break;
                case self::isObjectRule($rule, Rules\Date::class):
                    $constraint = RuleTypes::intersectConstraint($constraint, $rule->getTemplateType(Rules\Date::class, 'TValue'));
                    break;
                case self::isObjectRule($rule, Rules\Email::class):
                    $type = new StringType();
                    break;
                case self::isObjectRule($rule, Rules\Enum::class):
                    $enumType = RuleTypes::enumType($rule->getTemplateType(Rules\Enum::class, 'TEnum'));

                    if ($enumType !== null) {
                        $constraint = RuleTypes::intersectConstraint($constraint, $enumType);
                    }

                    break;
                case self::isObjectRule($rule, Rules\Numeric::class):
                    $constraint = RuleTypes::intersectConstraint($constraint, $rule->getTemplateType(Rules\Numeric::class, 'TValue'));
                    break;
                case self::isObjectRule($rule, self::STRING_RULE):
                    $type       = new StringType();
                    $constraint = RuleTypes::intersectConstraint($constraint, self::template($rule, self::STRING_RULE, 'TValue'));
                    break;
                case self::isObjectRule($rule, Rules\Dimensions::class):
                case self::isObjectRule($rule, Rules\File::class):
                    $type = new ObjectType(UploadedFile::class);
                    break;
                case self::isObjectRule($rule, Rules\Password::class):
                    $type = new StringType();
                    break;

                default:
                    foreach (['RequiredIf', 'RequiredUnless', 'ExcludeIf', 'ExcludeUnless'] as $modifier) {
                        $class = 'Illuminate\\Validation\\Rules\\' . $modifier;

                        if (! self::isObjectRule($rule, $class)) {
                            continue;
                        }

                        $condition = self::constantBoolean(self::template($rule, $class, 'TCondition'));
                        $unless    = in_array($modifier, ['RequiredUnless', 'ExcludeUnless'], true);
                        $required  = in_array($modifier, ['RequiredIf', 'RequiredUnless'], true);
                        $applies   = $condition === null || ! $unless ? $condition : ! $condition;
                        $flags     = $flags->both(new RuleFlags(
                            required: $required && $applies === true,
                            rejectsNull: $required && $applies === true,
                            possiblyExcluded: ! $required && $applies !== false,
                            excluded: ! $required && $applies === true,
                        ));
                        break;
                    }
            }
        }

        return new ValidationRule($type, $constraint, $keys, $groups, $flags);
    }

    private static function alternatives(Type $rule, bool $anyOf): ValidationRule
    {
        $class = $anyOf ? self::ANY_OF : self::CONDITIONAL_RULES;
        $types = [self::template($rule, $class, 'TRules')];

        if ($anyOf) {
            $arrays = $types[0]->getConstantArrays();
            $types  = count($arrays) === 1 && $arrays[0]->isList()->yes() ? $arrays[0]->getValueTypes() : [];
        } else {
            $condition = self::constantBoolean(self::template($rule, $class, 'TCondition'));
            $types     = match ($condition) {
                true => $types,
                false => [self::template($rule, $class, 'TDefaultRules')],
                null => [...$types, self::template($rule, $class, 'TDefaultRules')],
            };
        }

        $alternatives = [];

        foreach ($types as $type) {
            $alternative = self::fromType($type, requireList: $anyOf);

            // AnyOf exclusion affects a temporary validator, not the original value.
            if ($alternative === null || ($anyOf && $alternative->flags->possiblyExcluded)) {
                return $anyOf ? self::make([]) : self::degradedRule();
            }

            $alternatives[] = $alternative;
        }

        return new ValidationRule(
            type: new MixedType(true),
            anyOfRuleGroups: $alternatives === [] ? [] : [['rules' => $alternatives, 'anyOf' => $anyOf]],
            flags: $anyOf ? new RuleFlags() : RuleFlags::fromAlternatives(array_map(
                static fn (ValidationRule $alternative): RuleFlags => $alternative->flags,
                $alternatives,
            )),
        );
    }

    /** @param array<int, string> $parameterizedRules Rule names with unresolved parameters, indexed by their position in the list. */
    public static function fromType(Type $type, bool $requireList = false, array $parameterizedRules = []): ValidationRule|null
    {
        $strings = $type->getConstantStrings();

        if (count($strings) === 1) {
            return self::make($strings[0]->getValue());
        }

        if ($type->isObject()->yes()) {
            return self::make([$type]);
        }

        $arrays = $type->getConstantArrays();

        if (count($arrays) !== 1 || ($requireList && ! $arrays[0]->isList()->yes())) {
            return null;
        }

        if ($arrays[0]->getOptionalKeys() !== []) {
            // Optional modifiers can permit null, omit a value, or exclude its subtree.
            return self::degradedRule();
        }

        $rules = [];

        foreach ($arrays[0]->getValueTypes() as $index => $ruleType) {
            $strings = $ruleType->getConstantStrings();

            if (count($strings) === 1) {
                $rules[] = $strings[0]->getValue();
            } elseif ($ruleType->isObject()->yes()) {
                $rules[] = $ruleType;
            } elseif (isset($parameterizedRules[$index])) {
                $rules[] = [$parameterizedRules[$index], null];
            } else {
                return self::make([]);
            }
        }

        return self::make($rules);
    }

    private static function degradedRule(): ValidationRule
    {
        return new ValidationRule(new MixedType(true), flags: new RuleFlags(possiblyExcluded: true, degraded: true, prunesUnvalidatedKeys: null));
    }

    private static function isObjectRule(Type $rule, string $class): bool
    {
        $classType = new ObjectType($class);

        return $classType->getClassReflection() !== null && $classType->isSuperTypeOf($rule)->yes();
    }

    private static function template(Type $rule, string $class, string $template): Type
    {
        $reflection = (new ObjectType($class))->getClassReflection();

        return $reflection === null ? new MixedType() : $rule->getTemplateType($reflection->getName(), $template);
    }

    private static function constantBoolean(Type $type): bool|null
    {
        return $type->isNull()->yes() ? false : match ($type->getConstantScalarValues()) {
            [true] => true,
            [false] => false,
            default => null,
        };
    }

    /** @return list<ConstantIntegerType|ConstantStringType>|null */
    private static function constantArrayKeys(Type $type): array|null
    {
        $arrays = $type->getConstantArrays();

        if (count($arrays) !== 1 || $arrays[0]->getValueTypes() === []) {
            return null;
        }

        $keys = [];

        foreach ($arrays[0]->getValueTypes() as $valueType) {
            $value = RuleTypes::constantRuleString($valueType);

            if ($value === null) {
                return null;
            }

            $keys[] = $value->getValue();
        }

        return RuleTypes::arrayKeyTypes(str_getcsv(implode(',', $keys), escape: '\\'));
    }

    /** @param list<string>|null $parameters */
    private static function intParameter(array|null $parameters, int $index): int|null
    {
        $value = filter_var($parameters[$index] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }
}

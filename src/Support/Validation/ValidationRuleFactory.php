<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Numeric;
use Illuminate\Validation\Rules\Password;
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
use function is_bool;
use function is_string;
use function str_getcsv;

use const FILTER_VALIDATE_INT;

/** @internal */
final class ValidationRuleFactory
{
    private const ANY_OF = 'Illuminate\\Validation\\Rules\\AnyOf';

    private const STRING_RULE = 'Illuminate\\Validation\\Rules\\StringRule';

    private const CONDITIONAL_RULES = 'Illuminate\\Validation\\ConditionalRules';

    /** @param string|array<string|Type|array{string, null}> $rules */
    public static function make(string|array $rules): ValidationRule
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $ruleObjects = array_filter($rules, static fn ($rule) => $rule instanceof Type);
        $objectRule  = self::fromObjectRules($ruleObjects);

        $ruleStrings = array_filter($rules, static fn ($rule) => (is_string($rule) && $rule !== '') || is_array($rule));
        $stringRule  = self::fromStringRules($ruleStrings, $objectRule->type, $objectRule->constraintType);

        return new ValidationRule(
            type: $stringRule->type,
            constraintType: self::inObjectConstraint($ruleObjects, $stringRule->type, $stringRule->constraintType),
            allowedKeys: $stringRule->allowedKeys ?? $objectRule->allowedKeys,
            anyOfRuleGroups: $objectRule->anyOfRuleGroups,
            flags: $stringRule->flags->both($objectRule->flags),
        );
    }

    /** @param array<string|array{string, null}> $rules */
    private static function fromStringRules(array $rules, Type $type, Type|null $constraintType): ValidationRule
    {
        $possiblyUndefined     = false;
        $nullable              = false;
        $required              = false;
        $rejectsNull           = false;
        $possiblyExcluded      = false;
        $excluded              = false;
        $inValues              = null;
        $minimums              = [];
        $maximums              = [];
        $hasNumericRule        = false;
        $allowedKeys           = null;
        $prunesUnvalidatedKeys = false;

        foreach ($rules as $rule) {
            $prunesUnvalidatedKeys = $prunesUnvalidatedKeys || $rule === 'array' || $rule === 'list';
            $unknownParameters     = is_array($rule);
            [$rule, $parameters]   = ValidationRuleParser::parse($rule);
            $parameters            = $unknownParameters
                ? null
                : array_values(array_filter(
                    $parameters,
                    static fn ($parameter): bool => is_string($parameter),
                ));
            $rule                  = Str::snake($rule);
            $hasNumericRule        = $hasNumericRule || in_array($rule, ['integer', 'numeric', 'decimal'], true);

            switch ($rule) {
                case 'array':
                case 'array_keys':
                    if ($parameters !== null && $parameters !== [] && ($rule === 'array' || class_exists(RuleTypes::ARRAY_KEYS))) {
                        $allowedKeys = RuleTypes::arrayKeyTypes($parameters);
                    }

                    break;
                case 'in':
                    $inValues = $parameters ?? $inValues;
                    break;
                case 'nullable':
                    $nullable = true;
                    break;
                case 'exclude':
                    $possiblyExcluded = true;
                    $excluded         = true;
                    break;
                case 'exclude_if':
                case 'exclude_unless':
                case 'exclude_with':
                case 'exclude_without':
                    $possiblyExcluded = true;
                    break;
                case 'sometimes':
                    $possiblyUndefined = true;
                    break;
                // `present` guarantees the key exists just like `required`; it only
                // additionally allows the value to be empty, which doesn't affect the type.
                case 'required':
                case 'accepted':
                case 'declined':
                    $rejectsNull = true;
                    $required    = true;
                    break;
                case 'present':
                    $required = true;
                    break;
                case 'min':
                    $minimums[] = self::intParameter($parameters ?? [], 0);
                    break;
                case 'max':
                    $maximums[] = self::intParameter($parameters ?? [], 0);
                    break;
                case 'between':
                    $minimums[] = self::intParameter($parameters ?? [], 0);
                    $maximums[] = self::intParameter($parameters ?? [], 1);
                    break;
                case 'size':
                    $size       = self::intParameter($parameters ?? [], 0);
                    $minimums[] = $size;
                    $maximums[] = $size;
                    break;
            }

            $determinedType = RuleTypes::determineType($rule, $parameters ?? []);

            if ($determinedType === null) {
                continue;
            }

            $type = TypeCombinator::intersect($type, $determinedType);
        }

        if ($inValues !== null) {
            if ($type->isArray()->yes()) {
                $inType = RuleTypes::inParameterType($inValues, $type);

                if ($inType !== null) {
                    $constraintType = RuleTypes::intersectConstraint($constraintType, $inType);
                }
            } else {
                $type = RuleTypes::determineInType($inValues, $type);
            }
        }

        return new ValidationRule(
            type: RuleTypes::applyBounds($type, $minimums, $maximums, $hasNumericRule),
            constraintType: $constraintType,
            allowedKeys: $allowedKeys,
            flags: new RuleFlags(
                nullable: $nullable,
                possiblyUndefined: $possiblyUndefined,
                required: $required,
                rejectsNull: $rejectsNull,
                possiblyExcluded: $possiblyExcluded,
                excluded: $excluded,
                prunesUnvalidatedKeys: $prunesUnvalidatedKeys,
            ),
        );
    }

    /** @param array<Type> $rules */
    private static function fromObjectRules(array $rules): ValidationRule
    {
        $type            = new MixedType(true);
        $constraintType  = null;
        $allowedKeys     = null;
        $anyOfRuleGroups = [];
        $flags           = new RuleFlags();

        foreach ($rules as $rule) {
            switch (true) {
                case self::isObjectRule($rule, self::CONDITIONAL_RULES):
                    $conditionalRule = self::fromConditionalRule($rule);
                    $anyOfRuleGroups = [...$anyOfRuleGroups, ...$conditionalRule->anyOfRuleGroups];
                    $flags           = $flags->both($conditionalRule->flags);
                    break;
                case self::isObjectRule($rule, self::ANY_OF):
                    $alternatives = self::anyOfAlternatives(self::objectRuleTemplateType($rule, self::ANY_OF, 'TRules'));

                    if ($alternatives !== null) {
                        $anyOfRuleGroups[] = ['rules' => $alternatives, 'anyOf' => true];
                    }

                    break;
                case self::isObjectRule($rule, RuleTypes::ARRAY_KEYS):
                    $type        = RuleTypes::arrayType();
                    $allowedKeys = self::constantArrayKeys(self::objectRuleTemplateType($rule, RuleTypes::ARRAY_KEYS, 'TKeys'));
                    break;
                case self::isObjectRule($rule, ArrayRule::class):
                    $keysType    = $rule->getTemplateType(ArrayRule::class, 'TKeys');
                    $type        = RuleTypes::arrayType();
                    $allowedKeys = self::constantArrayKeys($keysType);
                    $flags       = $flags->both(new RuleFlags(
                        prunesUnvalidatedKeys: $keysType->isNull()->yes() || ($keysType->isArray()->yes() && $keysType->isIterableAtLeastOnce()->no())
                            ? true
                            : ($keysType->isArray()->yes() && $keysType->isIterableAtLeastOnce()->yes() ? false : null),
                    ));
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\Contains'):
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\DoesntContain'):
                    $type = RuleTypes::arrayType();
                    break;
                case self::isObjectRule($rule, Date::class):
                    $constraintType = RuleTypes::intersectConstraint($constraintType, $rule->getTemplateType(Date::class, 'TValue'));
                    break;
                case self::isObjectRule($rule, Email::class):
                    $type = new StringType();
                    break;
                case self::isObjectRule($rule, Enum::class):
                    $enumType = RuleTypes::enumType($rule->getTemplateType(Enum::class, 'TEnum'));

                    if ($enumType !== null) {
                        $constraintType = RuleTypes::intersectConstraint($constraintType, $enumType);
                    }

                    break;
                case self::isObjectRule($rule, Numeric::class):
                    $constraintType = RuleTypes::intersectConstraint($constraintType, $rule->getTemplateType(Numeric::class, 'TValue'));
                    break;
                case self::isObjectRule($rule, self::STRING_RULE):
                    $type           = new StringType();
                    $constraintType = RuleTypes::intersectConstraint(
                        $constraintType,
                        self::objectRuleTemplateType($rule, self::STRING_RULE, 'TValue'),
                    );
                    break;
                case self::isObjectRule($rule, Dimensions::class):
                case self::isObjectRule($rule, FileRule::class):
                    $type = new ObjectType(UploadedFile::class);
                    break;
                case self::isObjectRule($rule, Password::class):
                    $type = new StringType();
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\RequiredIf'):
                    $applies = self::conditionalObjectApplies($rule, 'Illuminate\\Validation\\Rules\\RequiredIf');
                    $flags   = $flags->both(new RuleFlags(required: $applies === true, rejectsNull: $applies === true));
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\RequiredUnless'):
                    $applies = self::conditionalObjectApplies(
                        $rule,
                        'Illuminate\\Validation\\Rules\\RequiredUnless',
                        true,
                    );
                    $flags   = $flags->both(new RuleFlags(required: $applies === true, rejectsNull: $applies === true));
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\ExcludeIf'):
                    $applies = self::conditionalObjectApplies($rule, 'Illuminate\\Validation\\Rules\\ExcludeIf');
                    $flags   = $flags->both(new RuleFlags(possiblyExcluded: $applies !== false, excluded: $applies === true));
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\ExcludeUnless'):
                    $applies = self::conditionalObjectApplies($rule, 'Illuminate\\Validation\\Rules\\ExcludeUnless', true);
                    $flags   = $flags->both(new RuleFlags(possiblyExcluded: $applies !== false, excluded: $applies === true));
                    break;
            }
        }

        return new ValidationRule(
            type: $type,
            constraintType: $constraintType,
            allowedKeys: $allowedKeys,
            anyOfRuleGroups: $anyOfRuleGroups,
            flags: $flags,
        );
    }

    private static function fromConditionalRule(Type $rule): ValidationRule
    {
        $alternatives = self::conditionalAlternatives($rule);

        if ($alternatives === null) {
            return self::degradedRule();
        }

        return new ValidationRule(
            type: new MixedType(true),
            anyOfRuleGroups: [['rules' => $alternatives, 'anyOf' => false]],
            flags: RuleFlags::fromAlternatives(array_map(
                static fn (ValidationRule $alternative): RuleFlags => $alternative->flags,
                $alternatives,
            )),
        );
    }

    /** A rule whose modifiers could not be resolved, so it may permit or exclude anything. */
    private static function degradedRule(): ValidationRule
    {
        return new ValidationRule(
            new MixedType(true),
            flags: new RuleFlags(possiblyExcluded: true, degraded: true, prunesUnvalidatedKeys: null),
        );
    }

    private static function isObjectRule(Type $rule, string $class): bool
    {
        $classType = new ObjectType($class);

        return $classType->getClassReflection() !== null && $classType->isSuperTypeOf($rule)->yes();
    }

    private static function objectRuleTemplateType(Type $rule, string $class, string $template): Type
    {
        $classReflection = (new ObjectType($class))->getClassReflection();

        return $classReflection === null
            ? new MixedType()
            : $rule->getTemplateType($classReflection->getName(), $template);
    }

    /** @param array<Type> $rules */
    private static function inObjectConstraint(array $rules, Type $type, Type|null $constraintType): Type|null
    {
        foreach ($rules as $rule) {
            if (! self::isObjectRule($rule, In::class)) {
                continue;
            }

            $inType = RuleTypes::inType($rule->getTemplateType(In::class, 'TValues'), $type);

            if ($inType === null) {
                continue;
            }

            $constraintType = RuleTypes::intersectConstraint($constraintType, $inType);
        }

        return $constraintType;
    }

    /** @return non-empty-list<ValidationRule>|null */
    private static function conditionalAlternatives(Type $rule): array|null
    {
        $condition = self::constantBoolean(self::objectRuleTemplateType($rule, self::CONDITIONAL_RULES, 'TCondition'));
        $types     = match ($condition) {
            true => [self::objectRuleTemplateType($rule, self::CONDITIONAL_RULES, 'TRules')],
            false => [self::objectRuleTemplateType($rule, self::CONDITIONAL_RULES, 'TDefaultRules')],
            null => [
                self::objectRuleTemplateType($rule, self::CONDITIONAL_RULES, 'TRules'),
                self::objectRuleTemplateType($rule, self::CONDITIONAL_RULES, 'TDefaultRules'),
            ],
        };

        $alternatives = [];

        foreach ($types as $type) {
            $alternative = self::fromType($type);

            if ($alternative === null) {
                return null;
            }

            $alternatives[] = $alternative;
        }

        return $alternatives;
    }

    private static function conditionalObjectApplies(Type $rule, string $class, bool $unless = false): bool|null
    {
        $condition = self::constantBoolean(self::objectRuleTemplateType($rule, $class, 'TCondition'));

        return $condition === null || ! $unless ? $condition : ! $condition;
    }

    private static function constantBoolean(Type $type): bool|null
    {
        if ($type->isNull()->yes()) {
            return false;
        }

        $values = $type->getConstantScalarValues();

        return count($values) === 1 && is_bool($values[0]) ? $values[0] : null;
    }

    /** @return list<ValidationRule>|null */
    private static function anyOfAlternatives(Type $type): array|null
    {
        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1 || ! $constantArrays[0]->isList()->yes()) {
            return null;
        }

        $alternatives = [];

        foreach ($constantArrays[0]->getValueTypes() as $alternativeType) {
            $alternative = self::fromType($alternativeType, requireList: true);

            // Exclusion only affects AnyOf's temporary validator, so an excluded
            // alternative cannot be discarded when constraining the original value.
            if ($alternative === null || $alternative->flags->possiblyExcluded) {
                return null;
            }

            $alternatives[] = $alternative;
        }

        return $alternatives === [] ? null : $alternatives;
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

        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1 || ($requireList && ! $constantArrays[0]->isList()->yes())) {
            return null;
        }

        if ($constantArrays[0]->getOptionalKeys() !== []) {
            // Optional modifiers can permit null, omit a value, or exclude its subtree.
            return self::degradedRule();
        }

        $rules = [];

        foreach ($constantArrays[0]->getValueTypes() as $index => $ruleType) {
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

    /** @return list<ConstantIntegerType|ConstantStringType>|null */
    private static function constantArrayKeys(Type $type): array|null
    {
        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1 || $constantArrays[0]->getValueTypes() === []) {
            return null;
        }

        $serializedKeys = [];

        foreach ($constantArrays[0]->getValueTypes() as $valueType) {
            $value = RuleTypes::constantRuleString($valueType);

            if ($value === null) {
                return null;
            }

            $serializedKeys[] = $value->getValue();
        }

        return RuleTypes::arrayKeyTypes(str_getcsv(implode(',', $serializedKeys), escape: '\\'));
    }

    /** @param list<int|string> $parameters */
    private static function intParameter(array $parameters, int $index): int|null
    {
        $value = filter_var($parameters[$index] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }
}

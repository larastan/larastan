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
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use ReflectionMethod;

use function array_filter;
use function array_values;
use function count;
use function explode;
use function filter_var;
use function implode;
use function in_array;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function str_contains;
use function str_getcsv;
use function version_compare;

use const FILTER_VALIDATE_INT;
use const LARAVEL_VERSION;

/** @internal */
final class ValidationRuleFactory
{
    private const ANY_OF = 'Illuminate\\Validation\\Rules\\AnyOf';

    private const ARRAY_KEYS = 'Illuminate\\Validation\\Rules\\ArrayKeys';

    private const STRING_RULE = 'Illuminate\\Validation\\Rules\\StringRule';

    private const CONDITIONAL_RULES = 'Illuminate\\Validation\\ConditionalRules';

    /** @param string|array<string|Type> $rules */
    public static function make(string|array $rules): ValidationRule
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $ruleObjects = array_filter($rules, static fn ($rule) => ! is_string($rule));
        $objectRule  = self::fromObjectRules($ruleObjects);

        $ruleStrings = array_filter($rules, static fn ($rule) => is_string($rule) && $rule !== '');
        $stringRule  = self::fromStringRules($ruleStrings, $objectRule->type, $objectRule->constraintType);

        return new ValidationRule(
            type: $stringRule->type,
            nullable: $stringRule->nullable,
            possiblyUndefined: $stringRule->possiblyUndefined || $objectRule->possiblyUndefined,
            required: $stringRule->required || $objectRule->required,
            constraintType: self::inObjectConstraint($ruleObjects, $stringRule->type, $stringRule->constraintType),
            allowedKeys: $stringRule->allowedKeys ?? $objectRule->allowedKeys,
            anyOfRuleGroups: $objectRule->anyOfRuleGroups,
            rejectsNull: $stringRule->rejectsNull || $objectRule->rejectsNull,
            possiblyExcluded: $stringRule->possiblyExcluded || $objectRule->possiblyExcluded,
            excluded: $stringRule->excluded || $objectRule->excluded,
            degraded: $objectRule->degraded,
            prunesUnvalidatedKeys: self::combinePruningRules($stringRule->prunesUnvalidatedKeys, $objectRule->prunesUnvalidatedKeys),
        );
    }

    /** @param array<string> $rules */
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
        $allowedKeys           = null;
        $prunesUnvalidatedKeys = false;

        foreach ($rules as $rule) {
            $prunesUnvalidatedKeys = $prunesUnvalidatedKeys || $rule === 'array' || $rule === 'list';
            [$rule, $parameters]   = ValidationRuleParser::parse($rule);
            $rule                  = Str::snake($rule);
            $parameters            = array_values(array_filter(
                $parameters,
                static fn ($parameter): bool => is_string($parameter),
            ));

            switch ($rule) {
                case 'array':
                case 'array_keys':
                    if ($parameters !== [] && ($rule === 'array' || class_exists(self::ARRAY_KEYS))) {
                        $allowedKeys = self::arrayKeyTypes($parameters);
                    }

                    break;
                case 'in':
                    $inValues = $parameters;
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
                    $minimums[] = self::intParameter($parameters, 0);
                    break;
                case 'max':
                    $maximums[] = self::intParameter($parameters, 0);
                    break;
                case 'between':
                    $minimums[] = self::intParameter($parameters, 0);
                    $maximums[] = self::intParameter($parameters, 1);
                    break;
                case 'size':
                    $size       = self::intParameter($parameters, 0);
                    $minimums[] = $size;
                    $maximums[] = $size;
                    break;
            }

            $determinedType = self::determineType($rule, $parameters);

            if ($determinedType === null) {
                continue;
            }

            $type = TypeCombinator::intersect($type, $determinedType);
        }

        if ($inValues !== null) {
            if ($type->isArray()->yes()) {
                $inType = self::inParameterType($inValues, $type);

                if ($inType !== null) {
                    $constraintType = self::intersectConstraint($constraintType, $inType);
                }
            } else {
                $type = self::determineInType($inValues, $type);
            }
        }

        return new ValidationRule(
            type: self::applyBounds($type, $minimums, $maximums),
            nullable: $nullable,
            possiblyUndefined: $possiblyUndefined,
            required: $required,
            constraintType: $constraintType,
            allowedKeys: $allowedKeys,
            rejectsNull: $rejectsNull,
            possiblyExcluded: $possiblyExcluded,
            excluded: $excluded,
            prunesUnvalidatedKeys: $prunesUnvalidatedKeys,
        );
    }

    /** @param array<Type> $rules */
    private static function fromObjectRules(array $rules): ValidationRule
    {
        $type                  = new MixedType(true);
        $constraintType        = null;
        $allowedKeys           = null;
        $anyOfRuleGroups       = [];
        $possiblyUndefined     = false;
        $required              = false;
        $possiblyExcluded      = false;
        $excluded              = false;
        $degraded              = false;
        $rejectsNull           = false;
        $prunesUnvalidatedKeys = false;

        foreach ($rules as $rule) {
            switch (true) {
                case self::isObjectRule($rule, self::CONDITIONAL_RULES):
                    $conditionalRule       = self::fromConditionalRule($rule);
                    $anyOfRuleGroups       = [...$anyOfRuleGroups, ...$conditionalRule->anyOfRuleGroups];
                    $possiblyUndefined     = $possiblyUndefined || $conditionalRule->possiblyUndefined;
                    $required              = $required || $conditionalRule->required;
                    $possiblyExcluded      = $possiblyExcluded || $conditionalRule->possiblyExcluded;
                    $excluded              = $excluded || $conditionalRule->excluded;
                    $degraded              = $degraded || $conditionalRule->degraded;
                    $rejectsNull           = $rejectsNull || $conditionalRule->rejectsNull;
                    $prunesUnvalidatedKeys = self::combinePruningRules($prunesUnvalidatedKeys, $conditionalRule->prunesUnvalidatedKeys);
                    break;
                case self::isObjectRule($rule, self::ANY_OF):
                    $alternatives = self::anyOfAlternatives(self::objectRuleTemplateType($rule, self::ANY_OF, 'TRules'));

                    if ($alternatives !== null) {
                        $anyOfRuleGroups[] = ['rules' => $alternatives, 'anyOf' => true];
                    }

                    break;
                case self::isObjectRule($rule, self::ARRAY_KEYS):
                    $type        = self::arrayType();
                    $allowedKeys = self::constantArrayKeys(self::objectRuleTemplateType($rule, self::ARRAY_KEYS, 'TKeys'));
                    break;
                case self::isObjectRule($rule, ArrayRule::class):
                    $keysType              = $rule->getTemplateType(ArrayRule::class, 'TKeys');
                    $type                  = self::arrayType();
                    $allowedKeys           = self::constantArrayKeys($keysType);
                    $prunesUnvalidatedKeys = self::combinePruningRules(
                        $prunesUnvalidatedKeys,
                        $keysType->isNull()->yes() || ($keysType->isArray()->yes() && $keysType->isIterableAtLeastOnce()->no())
                            ? true
                            : ($keysType->isArray()->yes() && $keysType->isIterableAtLeastOnce()->yes() ? false : null),
                    );
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\Contains'):
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\DoesntContain'):
                    $type = self::arrayType();
                    break;
                case self::isObjectRule($rule, Date::class):
                    $constraintType = self::intersectConstraint($constraintType, $rule->getTemplateType(Date::class, 'TValue'));
                    break;
                case self::isObjectRule($rule, Email::class):
                    $type = new StringType();
                    break;
                case self::isObjectRule($rule, Enum::class):
                    $enumType = self::enumType($rule->getTemplateType(Enum::class, 'TEnum'));

                    if ($enumType !== null) {
                        $constraintType = self::intersectConstraint($constraintType, $enumType);
                    }

                    break;
                case self::isObjectRule($rule, Numeric::class):
                    $constraintType = self::intersectConstraint($constraintType, $rule->getTemplateType(Numeric::class, 'TValue'));
                    break;
                case self::isObjectRule($rule, self::STRING_RULE):
                    $type           = new StringType();
                    $constraintType = self::intersectConstraint(
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
                    $applies     = self::conditionalObjectApplies($rule, 'Illuminate\\Validation\\Rules\\RequiredIf');
                    $required    = $required || $applies === true;
                    $rejectsNull = $rejectsNull || $applies === true;
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\RequiredUnless'):
                    $applies     = self::conditionalObjectApplies(
                        $rule,
                        'Illuminate\\Validation\\Rules\\RequiredUnless',
                        true,
                    );
                    $required    = $required || $applies === true;
                    $rejectsNull = $rejectsNull || $applies === true;
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\ExcludeIf'):
                    $applies          = self::conditionalObjectApplies($rule, 'Illuminate\\Validation\\Rules\\ExcludeIf');
                    $possiblyExcluded = $possiblyExcluded || $applies !== false;
                    $excluded         = $excluded || $applies === true;
                    break;
                case self::isObjectRule($rule, 'Illuminate\\Validation\\Rules\\ExcludeUnless'):
                    $applies          = self::conditionalObjectApplies($rule, 'Illuminate\\Validation\\Rules\\ExcludeUnless', true);
                    $possiblyExcluded = $possiblyExcluded || $applies !== false;
                    $excluded         = $excluded || $applies === true;
                    break;
            }
        }

        return new ValidationRule(
            type: $type,
            possiblyUndefined: $possiblyUndefined,
            required: $required,
            constraintType: $constraintType,
            allowedKeys: $allowedKeys,
            anyOfRuleGroups: $anyOfRuleGroups,
            possiblyExcluded: $possiblyExcluded,
            excluded: $excluded,
            degraded: $degraded,
            rejectsNull: $rejectsNull,
            prunesUnvalidatedKeys: $prunesUnvalidatedKeys,
        );
    }

    private static function fromConditionalRule(Type $rule): ValidationRule
    {
        $alternatives = self::conditionalAlternatives($rule);

        if ($alternatives === null) {
            return new ValidationRule(new MixedType(true), possiblyExcluded: true, degraded: true, prunesUnvalidatedKeys: null);
        }

        $required              = true;
        $excluded              = true;
        $possiblyUndefined     = false;
        $possiblyExcluded      = false;
        $rejectsNull           = true;
        $prunesUnvalidatedKeys = $alternatives[0]->prunesUnvalidatedKeys ?? null;
        $degraded              = false;

        foreach ($alternatives as $alternative) {
            $required          = $required && $alternative->required && ! $alternative->possiblyUndefined && ! $alternative->possiblyExcluded;
            $excluded          = $excluded && $alternative->excluded;
            $possiblyUndefined = $possiblyUndefined || $alternative->possiblyUndefined;
            $possiblyExcluded  = $possiblyExcluded || $alternative->possiblyExcluded;
            $rejectsNull       = $rejectsNull && $alternative->rejectsNull;
            $degraded          = $degraded || $alternative->degraded;
            if ($prunesUnvalidatedKeys === $alternative->prunesUnvalidatedKeys) {
                continue;
            }

            $prunesUnvalidatedKeys = null;
        }

        return new ValidationRule(
            type: new MixedType(true),
            possiblyUndefined: $possiblyUndefined,
            required: $required,
            anyOfRuleGroups: [['rules' => $alternatives, 'anyOf' => false]],
            possiblyExcluded: $possiblyExcluded,
            excluded: $excluded,
            rejectsNull: $rejectsNull,
            degraded: $degraded,
            prunesUnvalidatedKeys: $prunesUnvalidatedKeys,
        );
    }

    private static function combinePruningRules(bool|null $left, bool|null $right): bool|null
    {
        if ($left === true || $right === true) {
            return true;
        }

        return $left === null || $right === null ? null : false;
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

            $inType = self::inType($rule->getTemplateType(In::class, 'TValues'), $type);

            if ($inType === null) {
                continue;
            }

            $constraintType = self::intersectConstraint($constraintType, $inType);
        }

        return $constraintType;
    }

    /**
     * @param list<int|null> $minimums
     * @param list<int|null> $maximums
     */
    private static function applyBounds(Type $type, array $minimums, array $maximums): Type
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

    private static function intersectConstraint(Type|null $constraintType, Type $type): Type
    {
        return $constraintType === null
            ? $type
            : TypeCombinator::intersect($constraintType, $type);
    }

    /** @return list<ValidationRule>|null */
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
            if ($alternative === null || $alternative->possiblyExcluded) {
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

        if ($constantArrays[0]->getOptionalKeys() !== []) {
            // Optional modifiers can permit null, omit a value, or exclude its subtree.
            return new ValidationRule(new MixedType(true), possiblyExcluded: true, degraded: true, prunesUnvalidatedKeys: null);
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

        return self::arrayKeyTypes(str_getcsv(implode(',', $serializedKeys), escape: '\\'));
    }

    /**
     * @param list<string|null> $values
     *
     * @return list<ConstantIntegerType|ConstantStringType>|null
     */
    private static function arrayKeyTypes(array $values): array|null
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

    private static function enumType(Type $classStringType): Type|null
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

    /** @param list<int|string> $parameters */
    private static function intParameter(array $parameters, int $index): int|null
    {
        $value = filter_var($parameters[$index] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }

    /** @param list<string> $values */
    private static function determineInType(array $values, Type $baseType): Type
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

    private static function supportsStrictInComparison(string $version): bool
    {
        return version_compare($version, '12.67.0', '>=')
            && (version_compare($version, '13.0.0', '<') || version_compare($version, '13.26.0', '>='));
    }

    private static function arrayType(): Type
    {
        return new ArrayType(new MixedType(), new MixedType());
    }

    private static function looseIntegerType(): Type
    {
        return TypeUtils::toBenevolentUnion(TypeCombinator::union(self::numericType(), new ConstantBooleanType(true)));
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

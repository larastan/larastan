<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationRuleParser;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

use function array_keys;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_split;
use function str_contains;
use function str_replace;
use function strstr;
use function strval;

/**
 * Reads the rules of a `rules()` array into rule names and parameters.
 *
 * Rule objects are read as the rule strings Laravel renders them to.
 *
 * @internal
 */
final class RuleParser
{
    /** The most readings of one field that are still told apart. */
    private const MAX_READINGS = 16;

    /** Rule objects without parameters of interest, and a rule string accepting the same values. */
    private const RENDERED = [
        Rules\File::class => 'file',
        Rules\Dimensions::class => 'dimensions',
        Rules\Email::class => 'email',
        Rules\Password::class => 'string',
        'Illuminate\Validation\Rules\Contains' => 'contains',
        'Illuminate\Validation\Rules\DoesntContain' => 'doesnt_contain',
    ];

    /**
     * Splits an attribute name into its segments.
     *
     * @return non-empty-list<string>
     */
    public static function segments(string $attribute): array
    {
        return array_map(
            static fn (string $segment): string => str_replace('\.', '.', $segment),
            preg_split('/(?<!\\\\)\./', $attribute) ?: [$attribute],
        );
    }

    /**
     * Reads the rules of every field that is certain to be validated with them.
     *
     * @return array{array<string, non-empty-list<FieldRules>|null>, bool} The readings of each field, null when its rules
     *                                                                     are unknown, and whether other fields may exist.
     */
    public static function fields(Expr $rules, Scope $scope): array
    {
        return self::fieldsOf(self::entries($rules, $scope), $scope);
    }

    /**
     * Reads a returned expression, which may be one of several arrays, each read as a return of its own.
     *
     * @return non-empty-list<array{array<string, non-empty-list<FieldRules>|null>, bool}>
     */
    public static function returns(Expr $rules, Scope $scope): array
    {
        $type   = $scope->getType($rules);
        $arrays = $type->getConstantArrays();

        if ($rules instanceof Array_ || count($arrays) < 2 || ! $type->isConstantArray()->yes()) {
            return [self::fields($rules, $scope)];
        }

        return array_map(static fn (Type $array): array => self::fieldsOf(self::typeEntries($array), $scope), $arrays);
    }

    /**
     * @param iterable<array{Type, Expr|Type, bool}> $entries
     *
     * @return array{array<string, non-empty-list<FieldRules>|null>, bool}
     */
    private static function fieldsOf(iterable $entries, Scope $scope): array
    {
        $fields        = $uncertainRoots = $settledRoots = [];
        $open          = false;
        $unknownParent = false;

        foreach ($entries as [$keyType, $value, $certain]) {
            $keyType = $keyType->toArrayKey();

            // Integer attribute names are not modelled.
            if ($keyType->isInteger()->yes()) {
                $open = true;

                continue;
            }

            $names = array_map(static fn (ConstantScalarType $name): string => (string) $name->getValue(), $keyType->getConstantScalarTypes());
            $roots = array_map(static fn (string $name): string => self::segments($name)[0], $names);

            if (in_array('*', $roots, true)) {
                return [[], true];
            }

            if ($names === [] || ! $keyType->isConstantScalarValue()->yes()) {
                // An unknown key may replace any earlier field and be the parent of any later one.
                $fields        = $settledRoots = [];
                $open          = true;
                $unknownParent = true;

                continue;
            }

            foreach ($names as $i => $name) {
                if (! $certain || count($names) > 1) {
                    $open                       = true;
                    $uncertainRoots[$roots[$i]] = true;

                    continue;
                }

                unset($fields[$name]);
                $fields[$name] = $value instanceof Type ? self::fromType($value) : self::fromExpr($value, $scope);

                if (count(self::segments($name)) > 1) {
                    continue;
                }

                $settledRoots[$roots[$i]] = true;
                unset($uncertainRoots[$roots[$i]]);
            }
        }

        foreach (array_keys($fields) as $name) {
            $root = self::segments($name)[0];

            if (! isset($uncertainRoots[$root]) && (! $unknownParent || isset($settledRoots[$root]))) {
                continue;
            }

            unset($fields[$name]);
        }

        return [$fields, $open];
    }

    /**
     * Reads the rules of one field from their type.
     *
     * @return non-empty-list<FieldRules>|null
     */
    public static function fromType(Type $rules): array|null
    {
        $strings = $rules->getConstantStrings();

        // Each of several possible rule strings is a reading of its own.
        if ($strings !== [] && $rules->isConstantScalarValue()->yes() && $rules->isString()->yes()) {
            return count($strings) > self::MAX_READINGS ? null : array_map(
                static fn (ConstantStringType $string): FieldRules => array_reduce(
                    explode('|', $string->getValue()),
                    static fn (FieldRules $rules, string $rule): FieldRules => $rules->with(self::parse($rule)),
                    new FieldRules(),
                ),
                $strings,
            );
        }

        $array = self::constantArray($rules);

        if ($array !== null) {
            return $array->isList()->yes() && $array->getOptionalKeys() === []
                ? self::combine(array_values(array_map(self::element(...), $array->getValueTypes())))
                : null;
        }

        return $rules->isObject()->yes() ? self::element($rules) : null;
    }

    public static function parse(string $rule): FieldRules
    {
        [$name, $parameters] = ValidationRuleParser::parse($rule);

        // `str_getcsv()` reads an empty parameter as null.
        return new FieldRules([$name => [array_values(array_map(strval(...), $parameters))]]);
    }

    /** The name Laravel looks a rule up by. */
    private static function name(string $rule): string
    {
        return ValidationRuleParser::parse($rule)[0];
    }

    /** @return iterable<array{Type, Expr|Type, bool}> The key, the rules, and whether the key is certain to exist. */
    private static function entries(Expr $rules, Scope $scope): iterable
    {
        if (! $rules instanceof Array_) {
            yield from self::typeEntries($scope->getType($rules));

            return;
        }

        foreach ($rules->items as $item) {
            if ($item->unpack) {
                yield from self::typeEntries($scope->getType($item->value));
            } else {
                yield [$item->key === null ? new IntegerType() : $scope->getType($item->key), $item->value, true];
            }
        }
    }

    /** @return iterable<array{Type, Type, bool}> */
    private static function typeEntries(Type $rules): iterable
    {
        $array = self::constantArray($rules);

        if ($array === null) {
            yield [$rules->getIterableKeyType(), $rules->getIterableValueType(), false];

            return;
        }

        foreach ($array->getKeyTypes() as $i => $keyType) {
            yield [$keyType, $array->getValueTypes()[$i], ! $array->isOptionalKey($i)];
        }
    }

    /** @return non-empty-list<FieldRules>|null */
    private static function fromExpr(Expr $rules, Scope $scope): array|null
    {
        if (! $rules instanceof Array_) {
            return self::fromType($scope->getType($rules));
        }

        $elements = [];

        foreach ($rules->items as $item) {
            if ($item->key !== null || $item->unpack) {
                return null;
            }

            $elements[] = self::element($scope->getType($item->value)) ?? self::prefixed($item->value, $scope);
        }

        return self::combine($elements);
    }

    /**
     * Builds every reading from the alternatives of each rule.
     *
     * @param list<non-empty-list<FieldRules>|null> $elements
     *
     * @return non-empty-list<FieldRules>|null
     */
    private static function combine(array $elements): array|null
    {
        $readings = [new FieldRules()];

        foreach ($elements as $alternatives) {
            if ($alternatives === null || count($readings) * count($alternatives) > self::MAX_READINGS) {
                return null;
            }

            $readings = array_merge(...array_map(
                static fn (FieldRules $reading): array => array_map($reading->with(...), $alternatives),
                $readings,
            ));
        }

        return $readings;
    }

    /**
     * Reads one element of a rule list. Strings are not split on pipes here.
     *
     * @return non-empty-list<FieldRules>|null
     */
    private static function element(Type $rule): array|null
    {
        $string = self::constantString($rule);

        if ($string !== null) {
            return [self::parse($string)];
        }

        $classes = $rule->getObjectClassReflections();

        if (count($classes) !== 1 || ! $rule->isObject()->yes()) {
            return null;
        }

        $class = $classes[0]->getName();
        // A plain string, since some of the rule classes below do not exist in every Laravel version.
        $name     = $rule->getObjectClassNames()[0];
        $argument = static fn (string $template): Type => $rule->getTemplateType($class, $template);
        $required = [self::parse('required')];
        $exclude  = [self::parse('exclude')];
        $maybe    = [self::parse('exclude_if')];
        $none     = [new FieldRules()];

        return match ($name) {
            Rules\In::class => [self::rendered('in', $argument('TValues'), '"')],
            Rules\ArrayRule::class => self::arrayRule($argument('TKeys')),
            'Illuminate\Validation\Rules\ArrayKeys' => [self::rendered('array_keys', $argument('TKeys'))],
            Rules\Enum::class => [new FieldRules(types: [self::enum($argument('TEnum'))])],
            Rules\Numeric::class, Rules\Date::class, 'Illuminate\Validation\Rules\StringRule' => [new FieldRules(types: [$argument('TValue')])],
            'Illuminate\Validation\Rules\AnyOf' => [new FieldRules(types: [self::anyOf($argument('TRules'))])],
            ConditionalRules::class => self::conditional(
                $argument('TCondition'),
                self::fromType(ValidatedData::value($argument('TRules'))),
                self::fromType(ValidatedData::value($argument('TDefaultRules'))),
            ),
            Rules\RequiredIf::class => self::conditional($argument('TCondition'), $required, $none),
            'Illuminate\Validation\Rules\RequiredUnless' => self::conditional($argument('TCondition'), $none, $required),
            Rules\ExcludeIf::class => self::conditional($argument('TCondition'), $exclude, $none, $maybe),
            'Illuminate\Validation\Rules\ExcludeUnless' => self::conditional($argument('TCondition'), $none, $exclude, $maybe),
            default => [self::opaque($rule, $class)],
        };
    }

    /**
     * Reads a rule whose name is constant but whose parameters are not, like `'max:' . $limit`.
     *
     * @return non-empty-list<FieldRules>|null
     */
    private static function prefixed(Expr $rule, Scope $scope): array|null
    {
        $prefix = '';

        foreach (self::parts($rule) as $part) {
            $string = $part instanceof InterpolatedStringPart ? $part->value : self::constantString($scope->getType($part));

            if ($string === null) {
                break;
            }

            $prefix .= $string;
        }

        $name = strstr($prefix, ':', true);

        return $name === false ? null : [new FieldRules([self::name($name) => [null]])];
    }

    /** @return array<Expr|InterpolatedStringPart> */
    private static function parts(Expr $string): array
    {
        return match (true) {
            $string instanceof Concat => [...self::parts($string->left), ...self::parts($string->right)],
            $string instanceof InterpolatedString => $string->parts,
            default => [$string],
        };
    }

    /**
     * Renders a list of values the way Laravel's rule objects do, and reads the result back.
     *
     * @param ''|'"' $quote
     */
    private static function rendered(string $rule, Type $list, string $quote = ''): FieldRules
    {
        $values = [];

        foreach (self::constantArray($list)?->getValueTypes() ?? [new MixedType()] as $type) {
            $case    = $type->getEnumCaseObject();
            $scalars = ($case?->getBackingValueType() ?? $type)->getConstantScalarValues();
            $value   = match (true) {
                $case !== null && $case->getBackingValueType() === null => $case->getEnumCaseName(),
                count($scalars) === 1 => (string) $scalars[0],
                default => null,
            };

            // Backslashes escape quotes when Laravel reads the parameters back, which is not modelled.
            if ($value === null || str_contains($value, '\\')) {
                return new FieldRules([self::name($rule) => [null]]);
            }

            $values[] = $quote . str_replace('"', '""', $value) . $quote;
        }

        return self::parse($values === [] ? $rule : $rule . ':' . implode(',', $values));
    }

    /** @return non-empty-list<FieldRules> */
    private static function arrayRule(Type $keys): array
    {
        $unknown = new FieldRules([self::name('array') => [null]]);

        return match (true) {
            $keys->isConstantArray()->yes() => [self::rendered('array', $keys)],
            $keys->isIterableAtLeastOnce()->yes() => [$unknown],
            // Without keys the rule renders to a bare `array`.
            default => [self::parse('array'), $unknown],
        };
    }

    private static function enum(Type $class): Type
    {
        $cases = $class->getClassStringObjectType()->getEnumCases();

        if ($cases === []) {
            return new MixedType(true);
        }

        $values = TypeCombinator::union(...array_map(static fn (Type $case): Type => $case->getBackingValueType() ?? $case, $cases));

        // Numeric strings are coerced before they are matched against an integer-backed enum.
        return $values->isInteger()->yes()
            ? TypeUtils::toBenevolentUnion(TypeCombinator::union($values, TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType())))
            : $values;
    }

    private static function anyOf(Type $alternatives): Type
    {
        $array = self::constantArray($alternatives);

        if ($array === null || ! $array->isList()->yes()) {
            return new MixedType(true);
        }

        // An associative array is validated key by key, so it passes any alternative that requires nothing.
        $types = [new ArrayType(new MixedType(), new MixedType())];

        foreach ($array->getValueTypes() as $alternative) {
            $field = RuleTypes::field(self::fromType($alternative));

            if ($field === null || $field->unknown || ! $field->excluded->no() || $field->valueType() instanceof MixedType) {
                return new MixedType(true);
            }

            $types[] = $field->valueType();
        }

        return TypeUtils::toBenevolentUnion(TypeCombinator::union(...$types));
    }

    /**
     * @param non-empty-list<FieldRules>|null $then
     * @param non-empty-list<FieldRules>|null $else
     * @param non-empty-list<FieldRules>|null $either
     *
     * @return non-empty-list<FieldRules>|null
     */
    private static function conditional(Type $condition, array|null $then, array|null $else, array|null $either = null): array|null
    {
        $passes = $condition->isCallable()->no() ? $condition->toBoolean() : new BooleanType();

        return match (true) {
            $passes->isTrue()->yes() => $then,
            $passes->isFalse()->yes() => $else,
            $either !== null => $either,
            default => $then === null || $else === null ? null : [...$then, ...$else],
        };
    }

    /** Reads a rule object whose parameters are of no interest. Unknown objects are kept under their class name. */
    private static function opaque(Type $rule, string $class): FieldRules
    {
        foreach (self::RENDERED as $ruleClass => $rendered) {
            if ((new ObjectType($ruleClass))->isSuperTypeOf($rule)->yes()) {
                return self::parse($rendered);
            }
        }

        return new FieldRules([$class => [null]]);
    }

    private static function constantString(Type $type): string|null
    {
        $strings = $type->getConstantStrings();

        return count($strings) === 1 && $type->isConstantScalarValue()->yes() ? $strings[0]->getValue() : null;
    }

    private static function constantArray(Type $type): ConstantArrayType|null
    {
        $arrays = $type->getConstantArrays();

        return count($arrays) === 1 && $type->isConstantArray()->yes() ? $arrays[0] : null;
    }
}

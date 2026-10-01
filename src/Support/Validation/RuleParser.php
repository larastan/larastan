<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Support\Str;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rules;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
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
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_split;
use function str_contains;
use function str_getcsv;
use function str_replace;
use function strstr;
use function trim;

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
    public function fields(Expr $rules, Scope $scope): array
    {
        return $this->fieldsOf($this->entries($rules, $scope), $scope);
    }

    /**
     * Reads a returned expression, which may be one of several arrays, each read as a return of its own.
     *
     * @return non-empty-list<array{array<string, non-empty-list<FieldRules>|null>, bool}>
     */
    public function returns(Expr $rules, Scope $scope): array
    {
        $type   = $scope->getType($rules);
        $arrays = $type->getConstantArrays();

        if ($rules instanceof Array_ || count($arrays) < 2 || ! $type->isConstantArray()->yes()) {
            return [$this->fields($rules, $scope)];
        }

        return array_map(fn (Type $array): array => $this->fieldsOf($this->typeEntries($array), $scope), $arrays);
    }

    /**
     * @param iterable<array{Type, Expr|Type, bool}> $entries
     *
     * @return array{array<string, non-empty-list<FieldRules>|null>, bool}
     */
    private function fieldsOf(iterable $entries, Scope $scope): array
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
                $fields[$name] = $value instanceof Type ? $this->fromType($value) : $this->fromExpr($value, $scope);

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
    public function fromType(Type $rules): array|null
    {
        $strings = $rules->getConstantStrings();

        // Each of several possible rule strings is a reading of its own.
        if ($strings !== [] && $rules->isConstantScalarValue()->yes() && $rules->isString()->yes()) {
            $readings = array_map(
                fn (ConstantStringType $string): array|null => $this->combine(array_map(static fn (string $rule): array => [self::parse($rule)], explode('|', $string->getValue()))),
                $strings,
            );

            return in_array(null, $readings, true) || count($readings) > self::MAX_READINGS ? null : array_merge(...$readings);
        }

        $arrays = $rules->getConstantArrays();

        if (count($arrays) === 1 && $rules->isConstantArray()->yes()) {
            return $arrays[0]->isList()->yes() && $arrays[0]->getOptionalKeys() === []
                ? $this->combine(array_values(array_map($this->element(...), $arrays[0]->getValueTypes())))
                : null;
        }

        return $rules->isObject()->yes() ? $this->element($rules) : null;
    }

    public static function parse(string $rule): FieldRules
    {
        $parts      = explode(':', $rule, 2);
        $parameter  = $parts[1] ?? null;
        $name       = self::name($parts[0]);
        $parameters = match (true) {
            $parameter === null => [],
            in_array($name, ['Regex', 'NotRegex'], true) => [$parameter],
            default => array_map(static fn (string|null $value): string => (string) $value, str_getcsv($parameter, escape: '\\')),
        };

        return new FieldRules([$name => [$parameters]]);
    }

    /** The name Laravel looks a rule up by. */
    private static function name(string $rule): string
    {
        $name = Str::studly(trim($rule));

        return ['Int' => 'Integer', 'Bool' => 'Boolean'][$name] ?? $name;
    }

    /** @return iterable<array{Type, Expr|Type, bool}> The key, the rules, and whether the key is certain to exist. */
    private function entries(Expr $rules, Scope $scope): iterable
    {
        if (! $rules instanceof Array_) {
            yield from $this->typeEntries($scope->getType($rules));

            return;
        }

        foreach ($rules->items as $item) {
            if ($item->unpack) {
                yield from $this->typeEntries($scope->getType($item->value));
            } else {
                yield [$item->key === null ? new IntegerType() : $scope->getType($item->key), $item->value, true];
            }
        }
    }

    /** @return iterable<array{Type, Type, bool}> */
    private function typeEntries(Type $rules): iterable
    {
        $arrays = $rules->getConstantArrays();

        if (count($arrays) !== 1 || ! $rules->isConstantArray()->yes()) {
            yield [$rules->getIterableKeyType(), $rules->getIterableValueType(), false];

            return;
        }

        foreach ($arrays[0]->getKeyTypes() as $i => $keyType) {
            yield [$keyType, $arrays[0]->getValueTypes()[$i], ! $arrays[0]->isOptionalKey($i)];
        }
    }

    /** @return non-empty-list<FieldRules>|null */
    private function fromExpr(Expr $rules, Scope $scope): array|null
    {
        if (! $rules instanceof Array_) {
            return $this->fromType($scope->getType($rules));
        }

        $elements = [];

        foreach ($rules->items as $item) {
            if ($item->key !== null || $item->unpack) {
                return null;
            }

            $elements[] = $this->element($scope->getType($item->value)) ?? $this->prefixed($item->value, $scope);
        }

        return $this->combine($elements);
    }

    /**
     * Builds every reading from the alternatives of each rule.
     *
     * @param list<non-empty-list<FieldRules>|null> $elements
     *
     * @return non-empty-list<FieldRules>|null
     */
    private function combine(array $elements): array|null
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
    private function element(Type $rule): array|null
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
            Rules\In::class => [$this->rendered('in', $argument('TValues'), '"')],
            Rules\ArrayRule::class => $this->arrayRule($argument('TKeys')),
            'Illuminate\Validation\Rules\ArrayKeys' => [$this->rendered('array_keys', $argument('TKeys'))],
            Rules\Enum::class => [new FieldRules(types: [$this->enum($argument('TEnum'))])],
            Rules\Numeric::class, Rules\Date::class, 'Illuminate\Validation\Rules\StringRule' => [new FieldRules(types: [$argument('TValue')])],
            'Illuminate\Validation\Rules\AnyOf' => [new FieldRules(types: [$this->anyOf($argument('TRules'))])],
            ConditionalRules::class => self::conditional(
                $argument('TCondition'),
                $this->fromType(ValidatedData::value($argument('TRules'))),
                $this->fromType(ValidatedData::value($argument('TDefaultRules'))),
            ),
            Rules\RequiredIf::class => self::conditional($argument('TCondition'), $required, $none),
            'Illuminate\Validation\Rules\RequiredUnless' => self::conditional($argument('TCondition'), $none, $required),
            Rules\ExcludeIf::class => self::conditional($argument('TCondition'), $exclude, $none, $maybe),
            'Illuminate\Validation\Rules\ExcludeUnless' => self::conditional($argument('TCondition'), $none, $exclude, $maybe),
            default => [$this->opaque($rule, $class)],
        };
    }

    /**
     * Reads a rule whose name is constant but whose parameters are not, like `'max:' . $limit`.
     *
     * @return non-empty-list<FieldRules>|null
     */
    private function prefixed(Expr $rule, Scope $scope): array|null
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
    private function rendered(string $rule, Type $list, string $quote = ''): FieldRules
    {
        $values = [];
        $arrays = $list->getConstantArrays();

        foreach (count($arrays) === 1 && $list->isConstantArray()->yes() ? $arrays[0]->getValueTypes() : [new MixedType()] as $type) {
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
    private function arrayRule(Type $keys): array
    {
        $unknown = new FieldRules([self::name('array') => [null]]);

        return match (true) {
            $keys->isConstantArray()->yes() => [$this->rendered('array', $keys)],
            $keys->isIterableAtLeastOnce()->yes() => [$unknown],
            // Without keys the rule renders to a bare `array`.
            default => [self::parse('array'), $unknown],
        };
    }

    private function enum(Type $class): Type
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

    private function anyOf(Type $alternatives): Type
    {
        $arrays = $alternatives->getConstantArrays();

        if (count($arrays) !== 1 || ! $alternatives->isConstantArray()->yes() || ! $arrays[0]->isList()->yes()) {
            return new MixedType(true);
        }

        // An associative array is validated key by key, so it passes any alternative that requires nothing.
        $types = [new ArrayType(new MixedType(), new MixedType())];

        foreach ($arrays[0]->getValueTypes() as $alternative) {
            $field = RuleTypes::field($this->fromType($alternative));

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
    private function opaque(Type $rule, string $class): FieldRules
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
}

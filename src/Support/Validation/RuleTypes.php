<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use ReflectionMethod;

use function array_filter;
use function array_intersect;
use function array_intersect_key;
use function array_map;
use function array_values;
use function count;
use function filter_var;
use function in_array;
use function is_numeric;
use function max;
use function min;
use function version_compare;

use const FILTER_VALIDATE_INT;
use const LARAVEL_VERSION;

/**
 * Translates validation rules into the values that pass them.
 *
 * @internal
 */
final class RuleTypes
{
    private const CONDITIONAL_EXCLUSIONS = ['ExcludeIf' => true, 'ExcludeUnless' => true, 'ExcludeWith' => true, 'ExcludeWithout' => true];

    /** Size rules and the lower and upper bound their parameters set. */
    private const BOUNDS = [
        'Min' => [0, null],
        'Max' => [null, 0],
        'Between' => [0, 1],
        'Size' => [0, 0],
        'Gt' => [0, null],
        'Gte' => [0, null],
        'Lt' => [null, 0],
        'Lte' => [null, 0],
    ];

    /**
     * Summarises every reading of a field's rules.
     *
     * @param non-empty-list<FieldRules>|null $readings
     *
     * @return Field|null Null when the readings disagree on the array keys.
     */
    public static function field(array|null $readings): Field|null
    {
        $field = null;

        foreach ($readings ?? [] as $rules) {
            $field = $field === null ? self::read($rules) : $field->merge(self::read($rules));

            if ($field === null) {
                return null;
            }
        }

        return $field ?? Field::unknown();
    }

    /**
     * The values a single rule lets through, or null when it does not restrict them.
     *
     * @param list<string> $parameters
     */
    public static function valueType(string $name, array $parameters = []): Type|null
    {
        // Laravel versions without strict validation do not pass parameters to these rules.
        $strict = in_array('strict', $parameters, true)
            && in_array($name, ['Integer', 'Numeric', 'Boolean'], true)
            && (new ReflectionMethod(Validator::class, 'validate' . $name))->getNumberOfParameters() > 2;
        $scalar = TypeCombinator::union(new IntegerType(), new FloatType(), new StringType());

        return match ($name) {
            'String', 'Alpha', 'Email', 'Ip', 'Ipv4', 'Ipv6', 'MacAddress', 'Url', 'Uuid', 'Ulid', 'Timezone' => new StringType(),
            'Lowercase' => TypeCombinator::intersect(new StringType(), new AccessoryLowercaseStringType()),
            'Uppercase' => TypeCombinator::intersect(new StringType(), new AccessoryUppercaseStringType()),
            'Integer' => $strict ? new IntegerType() : self::numeric(),
            'Numeric' => $strict ? TypeCombinator::union(new IntegerType(), new FloatType()) : self::numeric(),
            'Decimal', 'Digits', 'DigitsBetween', 'MultipleOf' => self::numeric(),
            'Boolean' => $strict ? new BooleanType() : self::constants(true, false, 0, 1, '0', '1'),
            'Accepted' => self::constants(true, 1, '1', 'on', 'true', 'yes'),
            'Declined' => self::constants(false, 0, '0', 'off', 'false', 'no'),
            'Array', 'ArrayKeys', 'Contains', 'DoesntContain' => new ArrayType(new MixedType(), new MixedType()),
            'List' => TypeCombinator::intersect(new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new MixedType()), new AccessoryArrayListType()),
            // Dates may arrive as numbers, such as 20200101 or a timestamp.
            'AlphaDash', 'AlphaNum', 'Date', 'DateFormat', 'Regex', 'NotRegex', 'StartsWith', 'EndsWith' => $scalar,
            'Json' => TypeCombinator::union($scalar, new BooleanType()),
            'File', 'Image', 'Mimes', 'Mimetypes', 'Dimensions' => new ObjectType(UploadedFile::class),
            default => null,
        };
    }

    public static function numeric(): Type
    {
        return TypeCombinator::union(
            new IntegerType(),
            new FloatType(),
            TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType()),
        );
    }

    /**
     * Applies size rules to the integers of a type and to the emptiness of an array.
     *
     * @param array<string, list<list<string>|null>> $rules
     * @param bool                                   $numeric  Whether sizes compare the numeric value. Integers always do.
     * @param bool                                   $integral Whether only whole numbers pass, which makes `gt` and `lt` exclusive.
     */
    public static function bound(Type $type, array $rules, bool $numeric = false, bool $integral = true): Type
    {
        $limits = [[], []];

        foreach (array_intersect_key($rules, self::BOUNDS) as $name => $parameterLists) {
            foreach ($parameterLists as $parameters) {
                $sides = array_map(
                    static fn (int|null $position): int|false|null => $position === null ? null : filter_var($parameters[$position] ?? '', FILTER_VALIDATE_INT),
                    self::BOUNDS[$name],
                );

                if (in_array(false, $sides, true)) {
                    continue;
                }

                foreach ($sides as $side => $limit) {
                    if ($limit === null) {
                        continue;
                    }

                    $limits[$side][] = $limit + ($integral ? (['Gt' => 1, 'Lt' => -1][$name] ?? 0) : 0);
                }
            }
        }

        if ($limits === [[], []]) {
            return $type;
        }

        $minimum = $limits[0] === [] ? null : max($limits[0]);
        $range   = IntegerRangeType::fromInterval($minimum, $limits[1] === [] ? null : min($limits[1]));

        if ($type->isArray()->yes()) {
            return $minimum >= 1 ? TypeCombinator::intersect($type, new NonEmptyArrayType()) : $type;
        }

        if ($type->isString()->yes()) {
            return $minimum >= 1 ? TypeCombinator::intersect($type, new AccessoryNonEmptyStringType()) : $type;
        }

        if ($range instanceof NeverType || (! $numeric && ! $type->isInteger()->yes())) {
            return $type;
        }

        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($range): Type {
            if ($type instanceof UnionType) {
                return $traverse($type);
            }

            return $type->isInteger()->yes() ? TypeCombinator::intersect($type, $range) : $type;
        });
    }

    private static function read(FieldRules $fieldRules): Field
    {
        $rules = $fieldRules->rules;
        $type  = new MixedType(true);

        foreach ($rules as $name => $parameterLists) {
            foreach ($parameterLists as $parameters) {
                $type = self::narrow($type, self::valueType($name, $parameters ?? []));
            }
        }

        foreach ($fieldRules->types as $ruleType) {
            $type = self::narrow($type, $ruleType);
        }

        foreach ($rules['In'] ?? [] as $values) {
            $type = self::narrow($type, $values === null ? null : self::in($type, $values));
        }

        $type = self::bound($type, $rules, isset($rules['Integer']) || isset($rules['Numeric']) || isset($rules['Decimal']), isset($rules['Integer']));

        // Implicit rules run on null, so `nullable` cannot let it through.
        $rejectsNull = isset($rules['Required']) || isset($rules['Accepted']) || isset($rules['Declined']);

        if (isset($rules['Nullable']) && ! $rejectsNull) {
            $type = TypeCombinator::addNull($type);
        }

        $arrays   = [...($rules['Array'] ?? []), ...($rules['List'] ?? [])];
        $keyLists = array_filter([...$arrays, ...($rules['ArrayKeys'] ?? [])], static fn (array|null $keys): bool => $keys !== null && $keys !== []);

        return new Field(
            $type,
            isset($rules['Required']),
            ($rejectsNull || isset($rules['Present'])) && ! isset($rules['Sometimes']),
            match (true) {
                isset($rules['Exclude']) => TrinaryLogic::createYes(),
                array_intersect_key($rules, self::CONDITIONAL_EXCLUSIONS) !== [] => TrinaryLogic::createMaybe(),
                default => TrinaryLogic::createNo(),
            },
            TrinaryLogic::createFromBoolean(in_array([], $arrays, true)),
            count($keyLists) === 0 ? null : array_values(array_intersect(...$keyLists)),
        );
    }

    /**
     * The values of the given type that the `in` rule lets through.
     *
     * @param list<string> $values
     */
    private static function in(Type $type, array $values): Type|null
    {
        $numbers = array_filter($values, static fn (string $value): bool => $value === '' || is_numeric($value));
        $strings = self::constants(...$values);

        if ($type->isArray()->yes()) {
            return $numbers === [] ? new ArrayType(new MixedType(), $strings) : null;
        }

        if ($type->isString()->yes()) {
            // Laravel versions that compare loosely let any spelling of a number pass.
            $loose = ! self::comparesInStrictly(LARAVEL_VERSION);

            return ! $loose ? $strings : TypeCombinator::union(...array_map(
                static fn (string $value): Type => is_numeric($value) ? TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType()) : self::constants($value),
                $values,
            ));
        }

        if ($type->isInteger()->yes()) {
            $integers = array_filter($values, static fn (string $value): bool => (string) (int) $value === $value);

            return $integers === $values ? self::constants(...array_map('intval', $values)) : null;
        }

        return $numbers === [] ? $strings : null;
    }

    private static function comparesInStrictly(string $version): bool
    {
        return version_compare($version, '12.67.0', '>=')
            && (version_compare($version, '13.0.0', '<') || version_compare($version, '13.26.0', '>='));
    }

    private static function narrow(Type $type, Type|null $to): Type
    {
        $narrowed = $to === null ? $type : TypeCombinator::intersect($type, $to);

        return $narrowed instanceof NeverType ? $type : $narrowed;
    }

    private static function constants(bool|int|string ...$values): Type
    {
        return TypeCombinator::union(...array_map(ConstantTypeHelper::getTypeFromValue(...), $values));
    }
}

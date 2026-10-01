<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use Closure;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function array_intersect;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function is_int;
use function is_string;

/**
 * Laravel's dotted-path helpers, applied to the type of the data.
 *
 * @internal
 */
final class ValidatedData
{
    /**
     * The segments `data_get()` splits a key into.
     *
     * @return non-empty-list<string>|null Null unless the key is one constant without wildcards.
     */
    public static function segments(Type $key): array|null
    {
        $keys = $key->getConstantScalarValues();

        if (count($keys) !== 1 || (! is_string($keys[0]) && ! is_int($keys[0]))) {
            return null;
        }

        $segments = explode('.', (string) $keys[0]);

        return array_intersect($segments, ['*', '{first}', '{last}', '\*', '\{first}', '\{last}']) === [] ? $segments : null;
    }

    /**
     * The keys passed either as one array or as separate arguments.
     *
     * @param list<Type> $arguments
     *
     * @return list<Type>|null
     */
    public static function keys(array $arguments): array|null
    {
        if (count($arguments) !== 1 || $arguments[0]->isArray()->no()) {
            return $arguments;
        }

        $arrays = $arguments[0]->getConstantArrays();

        return count($arrays) === 1 && $arguments[0]->isConstantArray()->yes() && $arrays[0]->getOptionalKeys() === []
            ? array_values($arrays[0]->getValueTypes())
            : null;
    }

    /**
     * Follows a path into the data.
     *
     * @param list<string> $segments
     *
     * @return array{Type|null, TrinaryLogic} The value, null when it lies in data of unknown shape, and whether it exists.
     */
    public static function lookup(Type $data, array $segments): array
    {
        $exists = TrinaryLogic::createYes();

        foreach ($segments as $segment) {
            $arrays = TypeCombinator::intersect($data, new ArrayType(new MixedType(), new MixedType()));

            // Objects are read through their properties, which are not modelled.
            if ($data instanceof MixedType || ($arrays instanceof NeverType && ! $data->isObject()->no())) {
                return [null, TrinaryLogic::createMaybe()];
            }

            $key    = (new ConstantStringType($segment))->toArrayKey();
            $exists = $arrays instanceof NeverType
                ? TrinaryLogic::createNo()
                : $exists->and($arrays->hasOffsetValueType($key), TrinaryLogic::createFromBoolean($data->isArray()->yes())->or(TrinaryLogic::createMaybe()));

            if ($exists->no()) {
                break;
            }

            $data = $arrays->getOffsetValueType($key);
        }

        return [$data, $exists];
    }

    /**
     * Reads a key like `data_get()`, falling back to the default when the key is missing.
     *
     * @param (callable(Type): Type)|null $convert Applied to whichever of the value and the default is read.
     *
     * @return Type|null Null when the key is not a constant path.
     */
    public static function get(Type $data, Type $key, Type $default, callable|null $convert = null): Type|null
    {
        $segments = self::segments($key);

        if ($segments === null) {
            return null;
        }

        [$value, $exists] = self::lookup($data, $segments);
        $convert        ??= static fn (Type $type): Type => $type;

        return TypeCombinator::union(
            $exists->no() ? new NeverType() : $convert($value ?? new MixedType(true)),
            $exists->yes() ? new NeverType() : $convert(self::value($default)),
        );
    }

    /**
     * Copies the given paths into a new array.
     *
     * @param list<Type> $keys
     *
     * @return Type|null Null when a path is not constant or leads into data of unknown shape.
     */
    public static function only(Type $data, array $keys): Type|null
    {
        $result = new ConstantArrayType([], []);

        foreach ($keys as $key) {
            $segments = self::segments($key);

            if ($segments === null) {
                return null;
            }

            [$value, $exists] = self::lookup($data, $segments);

            if ($exists->no()) {
                continue;
            }

            if ($value === null) {
                return null;
            }

            $result = self::set($result, $segments, $value, ! $exists->yes());
        }

        return $result;
    }

    /**
     * Whether every path exists.
     *
     * @param list<Type> $keys
     */
    public static function has(Type $data, array $keys): TrinaryLogic
    {
        return TrinaryLogic::createYes()->and(...array_map(static function (Type $key) use ($data): TrinaryLogic {
            $segments = self::segments($key);

            return $segments === null ? TrinaryLogic::createMaybe() : self::lookup($data, $segments)[1];
        }, $keys));
    }

    /** The result of Laravel's `value()` helper, which calls closures. */
    public static function value(Type $value): Type
    {
        return TypeTraverser::map($value, static function (Type $type, callable $traverse): Type {
            if ($type instanceof UnionType) {
                return $traverse($type);
            }

            $isClosure = (new ObjectType(Closure::class))->isSuperTypeOf($type);

            return match (true) {
                $isClosure->no() => $type,
                $isClosure->yes() => TypeCombinator::union(...array_map(
                    static fn (ParametersAcceptor $variant): Type => $variant->getReturnType(),
                    $type->getCallableParametersAcceptors(new OutOfClassScope()),
                )),
                default => new MixedType(true),
            };
        });
    }

    /** @param non-empty-list<string> $segments */
    private static function set(Type $array, array $segments, Type $value, bool $optional): Type
    {
        $array  = $array->getConstantArrays()[0] ?? new ConstantArrayType([], []);
        $key    = (new ConstantStringType($segments[0]))->toArrayKey();
        $has    = $array->hasOffsetValueType($key);
        $nested = array_slice($segments, 1);

        if ($nested !== []) {
            $value = self::set($has->no() ? new ConstantArrayType([], []) : $array->getOffsetValueType($key), $nested, $value, $optional);
        }

        $builder = ConstantArrayTypeBuilder::createFromConstantArray($array);
        $builder->setOffsetValueType($key, $value, $optional && ! $has->yes());

        return $builder->getArray();
    }
}

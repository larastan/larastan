<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function array_intersect;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function strtolower;

/**
 * Models data_get() reads of request data and the scalar casts Laravel layers on them.
 *
 * @internal
 */
final class DataAccessorHelper
{
    /** Laravel resolves these segments against several keys at once, so no single value type applies. */
    private const SELECTORS = ['*', '{first}', '{last}', '\\*', '\\{first}', '\\{last}'];

    /**
     * The value type of input(), integer(), or boolean() reading $dataTypes.
     *
     * Each request class the receiver may be contributes its own array shape, kept
     * apart so that every cast sees the bounds of its own rules. Request::input()
     * omits uploaded files, which $excludesUploadedFiles declares.
     *
     * @param list<Type> $dataTypes
     * @param array<Arg> $args
     */
    public static function resolveAccessor(string $method, array $dataTypes, array $args, Scope $scope, bool $excludesUploadedFiles): Type|null
    {
        $method   = strtolower($method);
        $segments = [];

        if ($args !== []) {
            $keyType = $scope->getType($args[0]->value);

            if (! $keyType->isNull()->yes()) {
                $segments = self::parseKey($keyType);
            }
        }

        // Without a key, integer() and boolean() cast the whole array.
        if ($segments === null || ($segments === [] && $method !== 'input')) {
            return null;
        }

        $defaultType = count($args) > 1
            ? self::resolveDefaultType($scope->getType($args[1]->value), $scope)
            : match ($method) {
                'integer' => new ConstantIntegerType(0),
                'boolean' => new ConstantBooleanType(false),
                default => new NullType(),
            };

        $types = [];

        foreach ($dataTypes as $dataType) {
            [$valueType, $fallsBack] = self::select($dataType, $segments);

            if ($excludesUploadedFiles && self::mayContainUploadedFile($valueType)) {
                return null;
            }

            // The default is cast on its own: its integer members must not stand in for the value's bounds.
            $type         = self::cast($method, $valueType);
            $fallbackType = $fallsBack ? self::cast($method, $defaultType) : new NeverType();

            if ($type === null || $fallbackType === null) {
                return null;
            }

            $types[] = TypeCombinator::union($type, $fallbackType);
        }

        return TypeCombinator::union(...$types);
    }

    private static function cast(string $method, Type $type): Type|null
    {
        // Nothing is cast when the key is certainly missing.
        if ($type instanceof NeverType) {
            return $type;
        }

        return match ($method) {
            'integer' => self::castToInteger($type),
            'boolean' => self::castToBoolean($type),
            default => $type,
        };
    }

    /** @return list<Type>|null The dot-separated segments of an exact key, or null for dynamic keys and selectors. */
    public static function parseKey(Type $keyType): array|null
    {
        $constantScalars = $keyType->getConstantScalarTypes();

        if (count($constantScalars) !== 1) {
            return null;
        }

        $key             = $constantScalars[0];
        $constantStrings = $key->getConstantStrings();

        if (count($constantStrings) === 1) {
            $segments = explode('.', $constantStrings[0]->getValue());

            if (array_intersect($segments, self::SELECTORS) !== []) {
                return null;
            }

            return array_map(static fn (string $segment): Type => new ConstantStringType($segment), $segments);
        }

        return $key->isInteger()->yes() ? [$key] : null;
    }

    /**
     * Follows $segments into $type the way data_get() does.
     *
     * @param list<Type> $segments
     *
     * @return array{Type, bool} The value type reached, and whether the default may be returned instead.
     */
    public static function select(Type $type, array $segments): array
    {
        if ($segments === []) {
            return [$type, false];
        }

        $types     = [];
        $fallsBack = false;

        foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $memberType) {
            $hasKey = $memberType->hasOffsetValueType($segments[0]);

            if ($hasKey->no()) {
                $fallsBack = true;

                continue;
            }

            [$selectedType, $selectionFallsBack] = self::select($memberType->getOffsetValueType($segments[0]), array_slice($segments, 1));

            $types[]   = $selectedType;
            $fallsBack = $fallsBack || $selectionFallsBack || ! $hasKey->yes();
        }

        return [TypeCombinator::union(...$types), $fallsBack];
    }

    /** The value data_get() produces from a default, which it invokes when it is a Closure. */
    public static function resolveDefaultType(Type $type, Scope $scope): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($scope): Type {
            if ($type instanceof UnionType) {
                return $traverse($type);
            }

            $isClosure = (new ObjectType(Closure::class))->isSuperTypeOf($type);

            if ($isClosure->yes()) {
                return $type->getCallableParametersAcceptors($scope)[0]->getReturnType();
            }

            return $isClosure->maybe() ? new MixedType() : $type;
        });
    }

    /** The result of an (int) cast, or null when the cast is invalid for a possible value. */
    public static function castToInteger(Type $type): Type|null
    {
        $types      = [];
        $hasInteger = false;
        $hasBounded = false;

        foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $memberType) {
            if ($memberType->isInteger()->yes()) {
                $hasInteger = true;
                $types[]    = $memberType;

                continue;
            }

            if ($memberType->isConstantScalarValue()->yes()) {
                $types[] = $memberType->toInteger();

                continue;
            }

            // A numeric rule applies its bounds to every representation, so a float or numeric string truncates into the integer member.
            if ($memberType->isFloat()->yes() || $memberType->isNumericString()->yes()) {
                $hasBounded = true;

                continue;
            }

            $castType = $memberType->toInteger();

            if ($castType instanceof ErrorType) {
                return null;
            }

            $types[] = $castType;
        }

        return $hasBounded && ! $hasInteger ? $type->toInteger() : TypeCombinator::union(...$types);
    }

    /** The result of filter_var() with FILTER_VALIDATE_BOOLEAN. */
    public static function castToBoolean(Type $type): Type
    {
        $truthyType = TypeCombinator::union(
            new ConstantBooleanType(true),
            new ConstantIntegerType(1),
            new ConstantStringType('1'),
            new ConstantStringType('true'),
            new ConstantStringType('on'),
            new ConstantStringType('yes'),
        );

        if ($truthyType->isSuperTypeOf($type)->yes()) {
            return new ConstantBooleanType(true);
        }

        $falsyType = TypeCombinator::union(
            new ConstantBooleanType(false),
            new ConstantIntegerType(0),
            new ConstantStringType('0'),
            new ConstantStringType('false'),
            new ConstantStringType('off'),
            new ConstantStringType('no'),
            new ConstantStringType(''),
            new NullType(),
        );

        if ($falsyType->isSuperTypeOf($type)->yes()) {
            return new ConstantBooleanType(false);
        }

        return TypeCombinator::union(new ConstantBooleanType(true), new ConstantBooleanType(false));
    }

    /** Whether a value of $type can be, or contain, an uploaded file; mixed is not counted because it stays sound either way. */
    private static function mayContainUploadedFile(Type $type): bool
    {
        $uploadedFileType = new ObjectType(UploadedFile::class);
        $found            = false;

        TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$found, $uploadedFileType): Type {
            if ($type instanceof UnionType || $type instanceof IntersectionType || $type->isArray()->yes()) {
                return $traverse($type);
            }

            if (! $type instanceof MixedType && ! $uploadedFileType->isSuperTypeOf($type)->no()) {
                $found = true;
            }

            return $type;
        });

        return $found;
    }
}

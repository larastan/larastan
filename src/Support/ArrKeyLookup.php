<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use ArrayAccess;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function array_map;
use function explode;
use function str_contains;

/**
 * Follows a key through an array like Arr::get(), asking PHPStan whether each segment exists.
 *
 * @internal
 */
final class ArrKeyLookup
{
    /**
     * Returns, for each possible key, the lookups Arr::get() may take: the literal key, then its
     * "dot" path unless the literal key always exists. Each lookup is whether it finds a value and
     * the access that reads it, or the first missing access.
     *
     * @return list<non-empty-list<array{TrinaryLogic, ArrayDimFetch}>>|null null when the key isn't
     *                                                                        an integer or constant string
     */
    public static function find(Scope $scope, Expr $array, Expr $key): array|null
    {
        $keyType = $scope->getType($key);

        if ($keyType->isInteger()->yes()) {
            return [[self::lookup($scope, $array, [$key])]];
        }

        $constantStrings = $keyType->getConstantStrings();

        if ($constantStrings === []) {
            return null;
        }

        $results = [];

        foreach ($constantStrings as $constantString) {
            $value   = $constantString->getValue();
            $literal = self::lookup($scope, $array, [new String_($value)]);

            $results[] = $literal[0]->yes() || ! str_contains($value, '.')
                ? [$literal]
                : [$literal, self::lookup($scope, $array, array_map(static fn ($segment) => new String_($segment), explode('.', $value)))];
        }

        return $results;
    }

    /**
     * @param non-empty-list<Expr> $keys
     *
     * @return array{TrinaryLogic, ArrayDimFetch}
     */
    private static function lookup(Scope $scope, Expr $array, array $keys): array
    {
        $exists = TrinaryLogic::createYes();
        $value  = $array;

        foreach ($keys as $key) {
            $exists = $exists->and(self::keyExists($scope->getType($value), $scope->getType($key)));
            $value  = new ArrayDimFetch($value, $key);

            if ($exists->no()) {
                break;
            }
        }

        return [$exists, $value];
    }

    /**
     * Arr::get() only reads arrays and ArrayAccess objects, never string offsets. Arrays cast
     * numeric-string keys like '0' to integers; offsetExists() receives them unchanged.
     */
    private static function keyExists(Type $type, Type $keyType): TrinaryLogic
    {
        return $type->isArray()->and($type->hasOffsetValueType($keyType->toArrayKey()))
            ->or((new ObjectType(ArrayAccess::class))->isSuperTypeOf($type)->result->and($type->hasOffsetValueType($keyType)));
    }
}

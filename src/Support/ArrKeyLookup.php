<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use ArrayAccess;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

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
     * Returns, for each possible key, whether it exists, the array accesses that can read it,
     * and the access to report when it doesn't exist.
     *
     * @return list<array{TrinaryLogic, list<ArrayDimFetch>, ArrayDimFetch}>|null null when the key
     *                                                                       isn't an integer or constant string
     */
    public static function find(Scope $scope, Expr $array, Expr $key, Type $keyType): array|null
    {
        if ($keyType->isInteger()->yes()) {
            [$exists, $access] = self::lookup($scope, $array, [$key]);

            return [[$exists, $exists->no() ? [] : [$access], $access]];
        }

        if (! $keyType->isString()->yes() || ! $keyType->isConstantScalarValue()->yes()) {
            return null;
        }

        $results = [];

        foreach ($keyType->getConstantStrings() as $constantString) {
            $value = $constantString->getValue();

            [$literalExists, $literalAccess] = self::lookup($scope, $array, [new String_($value)]);

            // "Dot" segments are only followed when the literal key is missing.
            if ($literalExists->yes() || ! str_contains($value, '.')) {
                $results[] = [$literalExists, $literalExists->no() ? [] : [$literalAccess], $literalAccess];

                continue;
            }

            $segments = [];

            foreach (explode('.', $value) as $segment) {
                $segments[] = new String_($segment);
            }

            [$pathExists, $pathAccess] = self::lookup($scope, $array, $segments);

            $accesses = [];

            foreach ([[$literalExists, $literalAccess], [$pathExists, $pathAccess]] as [$exists, $access]) {
                if ($exists->no()) {
                    continue;
                }

                $accesses[] = $access;
            }

            $results[] = [$literalExists->or($pathExists), $accesses, $pathAccess];
        }

        return $results;
    }

    /**
     * @param non-empty-list<Expr> $keys
     *
     * @return array{TrinaryLogic, ArrayDimFetch} whether the keys exist, and the access that reads them
     *                                            or the first missing one
     */
    private static function lookup(Scope $scope, Expr $array, array $keys): array
    {
        $exists = TrinaryLogic::createYes();
        $value  = $array;

        foreach ($keys as $key) {
            $exists = $exists->and(self::keyExists($scope, $value, $key));
            $value  = new ArrayDimFetch($value, $key);

            if ($exists->no()) {
                break;
            }
        }

        return [$exists, $value];
    }

    /**
     * Like Arr::exists(): offsetExists() for ArrayAccess objects, array_key_exists() for arrays.
     */
    private static function keyExists(Scope $scope, Expr $array, Expr $key): TrinaryLogic
    {
        $isArrayAccess = $scope->getType(new Instanceof_($array, new FullyQualified(ArrayAccess::class)))->isTrue();

        if ($isArrayAccess->maybe()) {
            return TrinaryLogic::createMaybe();
        }

        $check = $isArrayAccess->yes()
            ? new MethodCall($array, 'offsetExists', [new Arg($key)])
            : new FuncCall(new FullyQualified('array_key_exists'), [new Arg($key), new Arg($array)]);

        return $scope->getType($check)->isTrue();
    }
}

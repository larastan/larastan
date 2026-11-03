<?php

namespace ArrKeyExistence;

use App\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

function issue1727(): void
{
    $array = [
        'foo' => '123',
    ];

    Arr::pull($array, 'bar');
}

class CustomArr extends Arr
{
}

/**
 * @param array{foo: string, bar?: int, nested: array{deep: bool}, word: string} $shape
 * @param array<string, int> $map
 * @param Collection<string, int> $collection
 * @param array{foo: string}|null $nullableShape
 * @param 'foo'|'missing' $fooOrMissing
 * @param 'missing'|'other' $missingKeys
 */
function test(array $shape, array $map, Collection $collection, ?array $nullableShape, string $fooOrMissing, string $missingKeys, string $anyKey, mixed $mixed): void
{
    Arr::get($shape, 'foo');
    Arr::get($shape, 'bar');
    Arr::get($shape, 'nested.deep');
    Arr::get($shape, null);
    Arr::get($shape, $fooOrMissing);
    Arr::get($shape, $anyKey);
    Arr::get($map, 'anything');
    Arr::get($collection, 'anything');
    Arr::get($mixed, 'anything');
    Arr::get((new User())->toArray(), 'anything');

    Arr::get($shape, 'missing');
    Arr::get($shape, 'missing', 'default');
    Arr::get($shape, 'nested.missing');
    Arr::get($shape, 'word.0');
    Arr::get($shape, $missingKeys);
    Arr::get($nullableShape, 'missing');
    Arr::get(key: 'missing', array: $shape);
    Arr::GET($shape, 'missing');
    CustomArr::get($shape, 'missing');
    Arr::pull($shape, 'missing');
}

/** @param array{foo: string}|ArrKeyExistenceAccess $shapeOrAccess */
function literalDotKeysAndArrayAccess(array|ArrKeyExistenceAccess $shapeOrAccess, ArrKeyExistenceAccess $access): void
{
    Arr::get(['a.b' => 1], 'a.b');
    Arr::get($shapeOrAccess, 'missing');
    Arr::get($access, 'missing');
    Arr::get($access, '0');
}

class OverridingArr extends Arr
{
    public static function get($array, $key, $default = null)
    {
        return 'overridden';
    }
}

/** @param array{foo: string} $shape */
function overriddenGet(array $shape): void
{
    OverridingArr::get($shape, 'missing');
}

/** @implements \ArrayAccess<string, int> */
abstract class ArrKeyExistenceAccess implements \ArrayAccess
{
}

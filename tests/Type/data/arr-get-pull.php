<?php

namespace ArrGetPull;

use App\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

/** @implements \ArrayAccess<string, int> */
class ArrGetPullAccess implements \ArrayAccess
{
    public function offsetExists(mixed $offset): bool
    {
        return true;
    }

    public function offsetGet(mixed $offset): int
    {
        return 1;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }
}

/**
 * @param array{foo: string, bar?: int, nullable: string|null, nested: array{deep: bool, maybe?: int}, word: string} $shape
 * @param list<array{name: string}> $rows
 * @param array<string, int> $map
 * @param Collection<string, int> $collection
 * @param array{a: int}|null $nullableShape
 * @param 'foo'|'bar' $fooOrBar
 */
function get(
    array $shape,
    array $rows,
    array $map,
    Collection $collection,
    ?array $nullableShape,
    ?string $nullableKey,
    string $anyKey,
    int $anyInt,
    string $fooOrBar,
): void {
    assertType('string', Arr::get($shape, 'foo'));
    assertType('int|null', Arr::get($shape, 'bar'));
    assertType('string|null', Arr::get($shape, 'nullable', 'default'));
    assertType('null', Arr::get($shape, 'missing'));
    assertType("'default'", Arr::get($shape, 'missing', 'default'));
    assertType('string|null', Arr::get($shape, $fooOrBar === 'foo' ? 'foo' : 'missing'));
    assertType('int|string|null', Arr::get($shape, $fooOrBar));
    assertType('string', Arr::get(key: 'foo', array: $shape));

    // "dot" notation
    assertType('bool', Arr::get($shape, 'nested.deep'));
    assertType('int|null', Arr::get($shape, 'nested.maybe'));
    assertType('null', Arr::get($shape, 'nested.missing'));
    assertType('null', Arr::get($shape, 'word.0'));
    assertType('string|null', Arr::get($rows, '0.name'));

    // Other keys keep the declared type
    assertType('mixed', Arr::get($shape, null));
    assertType('mixed', Arr::get($shape, $nullableKey));
    assertType('mixed', Arr::get($map, $anyKey));

    // Defaults go through value(), which only calls a Closure
    assertType('1', Arr::get($shape, 'missing', static fn () => 1));
    assertType("'strtoupper'", Arr::get($shape, 'missing', 'strtoupper'));

    assertType('array{name: string}|null', Arr::get($rows, $anyInt));
    assertType('int|null', Arr::get($collection, 'key'));
    assertType('int|null', Arr::get($nullableShape, 'a'));

    // Unsealed shapes may hold any other key
    assertType('mixed', Arr::get((new User())->toArray(), 'anything'));
}

/**
 * @param array{'a.b': int, a: array{b: string}} $dotKey
 * @param array{'a.b'?: int, a: array{b: string}} $maybeDotKey
 * @param array{foo: string}|ArrGetPullAccess $shapeOrAccess
 */
function getLiteralDotKeysAndArrayAccess(array $dotKey, array $maybeDotKey, array|ArrGetPullAccess $shapeOrAccess, ArrGetPullAccess $access): void
{
    // A literal key wins over the "dot" path
    assertType('int', Arr::get($dotKey, 'a.b'));
    assertType('int|string', Arr::get($maybeDotKey, 'a.b'));

    assertType('int|null', Arr::get($access, 'key'));
    assertType('int|null', Arr::get($access, '0'));
    assertType('int|string|null', Arr::get($shapeOrAccess, 'foo'));
}

/**
 * @param array{foo: string, bar?: int, nested: array{deep: bool, keep: int}, optional?: array{deep: bool}, nullable: array{deep: bool}|null, 'dot.key': int} $shape
 * @param list<int> $list
 * @param array<string, int> $map
 * @param 'foo'|'bar' $fooOrBar
 */
function pull(array $shape, array $list, array $map, string $anyKey, string $fooOrBar): void
{
    $array = $shape;
    assertType('string', Arr::pull($array, 'foo'));
    assertType("array{bar?: int, nested: array{deep: bool, keep: int}, optional?: array{deep: bool}, nullable: array{deep: bool}|null, 'dot.key': int}", $array);

    $array = $shape;
    assertType('int|null', Arr::pull($array, 'bar'));
    assertType("array{foo: string, nested: array{deep: bool, keep: int}, optional?: array{deep: bool}, nullable: array{deep: bool}|null, 'dot.key': int}", $array);

    $array = $shape;
    assertType("'default'", Arr::pull($array, 'missing', 'default'));
    assertType("array{foo: string, bar?: int, nested: array{deep: bool, keep: int}, optional?: array{deep: bool}, nullable: array{deep: bool}|null, 'dot.key': int}", $array);

    $array = $shape;
    Arr::pull($array, $fooOrBar);
    assertType("array{foo?: string, bar?: int, nested: array{deep: bool, keep: int}, optional?: array{deep: bool}, nullable: array{deep: bool}|null, 'dot.key': int}", $array);

    // Keys in "dot" notation keep the declared type
    $array = $shape;
    assertType('bool', Arr::pull($array, 'nested.deep'));
    assertType('array', $array);

    assertType('int|null', Arr::pull($list, 0));
    assertType('array<int<1, max>, int>', $list);

    assertType('mixed', Arr::pull($map, $anyKey));
    assertType('array', $map);
}

/**
 * @param list<string> $list
 * @param '0'|'1' $zeroOrOne
 */
function pullNumericStringKeys(array $list, string $zeroOrOne): void
{
    $array = $list;
    Arr::pull($array, '0');
    assertType('array<int<1, max>, string>', $array);

    $array = $list;
    Arr::pull($array, '1');
    assertType('array<0|int<2, max>, string>', $array);

    $array = $list;
    Arr::pull($array, $zeroOrOne);
    assertType('array<int<0, max>, string>', $array);
}

/** @param array{'a.b': int, a: array{b: string}, c: int} $dotKey */
function pullLiteralDotKey(array $dotKey): void
{
    assertType('int', Arr::pull($dotKey, 'a.b'));
    assertType('array{a: array{b: string}, c: int}', $dotKey);
}

class ArrGetPullOverridingArr extends Arr
{
    /** @return 'overridden' */
    public static function get($array, $key, $default = null)
    {
        return 'overridden';
    }
}

/** @param array{foo: string} $shape */
function overriddenGet(array $shape): void
{
    assertType("'overridden'", ArrGetPullOverridingArr::get($shape, 'missing'));
}

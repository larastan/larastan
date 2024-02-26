<?php

namespace ArrGet;

use ArrayAccess;
use Illuminate\Support\Arr;

use function PHPStan\Testing\assertType;

/**
 * @param ArrayAccess $arrayAccessMixed
 * @param ArrayAccess<string, int<1, max>> $arrayAccess
 * @param array{foo: 1}|array{bar: 2} $union
 * @param array<string, string> $iterable
 */
function test(
    ArrayAccess $arrayAccessMixed,
    ArrayAccess $arrayAccess,
    array $union,
    array $iterable,
    ?string $nullableKey,
): void
{
    assertType('mixed', Arr::get($arrayAccessMixed, 1, []));
    assertType('array{}', Arr::get($arrayAccess, 1, []));
    assertType('array{}|ArrayAccess<string, int<1, max>>|int<1, max>', Arr::get($arrayAccess, $nullableKey, []));
    assertType('int<0, max>', Arr::get($arrayAccess, 'key', 0));
    assertType('array{foo: 1}', Arr::get(['foo' => 1], null));
    assertType('1|array{foo: 1}|null', Arr::get(['foo' => 1], $nullableKey));
    assertType('2', Arr::get(['foo' => 1], 'bar', 2));
    assertType('null', Arr::get(['foo' => 1], 'bar'));
    assertType('2|3', Arr::get($union, 'bar', 3));
    assertType('array{foo: 1}', Arr::pull(['foo' => 1], null));
    assertType('2', Arr::pull(['foo' => 1], 'bar', 2));
    assertType('null', Arr::pull(['foo' => 1], 'bar'));
    assertType('2|3', Arr::pull($union, 'bar', 3));
    assertType('3|string', Arr::get($iterable, 'bar', 3));
    assertType('string|null', Arr::get($iterable, 'bar'));
    assertType('null', Arr::get($iterable, 1));
    assertType('null', Arr::pull($iterable, 1));
}

/**
 * @param array{foo: 1, bar: 2} $arr
 */
function testPull(array $arr): void
{
    $pulled = Arr::pull($arr, 'foo');

    assertType('1', $pulled);
    assertType('array{bar: 2}', $arr);
}

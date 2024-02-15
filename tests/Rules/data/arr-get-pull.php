<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

/**
 * @param array<string, mixed> $mixedArray
 * @param array<'foo', mixed> $specificKeys
 */
function test(array $mixedArray, array $specificKeys, array $unspecified, mixed $key): void
{
    Arr::get(['foo' => 1], 'bar');
    Arr::get(['foo' => 1], 'foo');
    Arr::pull(['foo' => 1], 'foo');
    Arr::pull(['foo' => 1], 'bar');

    Arr::get($specificKeys, 'bar');
    Arr::pull($specificKeys, 'bar');

    Arr::get($mixedArray, 'any');
    Arr::pull($mixedArray, 'any');

    Arr::get($unspecified, $key);

    Arr::get([1,2,3], 2);
    Arr::get([1,2,3], 3);
    Arr::pull([1,2,3], 2);
    Arr::pull([1,2,3], 3);
}

/**
 * @param array{foo: 1, bar: 2} $shaped1
 * @param array{foo: 1, bar: 2} $shaped2
 */
function testShape(array $shaped1, array $shaped2): void
{
    Arr::get($shaped1, 'foo');
    Arr::pull($shaped1, 'foo');

    Arr::get($shaped2, 'invalid');
    Arr::pull($shaped2, 'invalid');
}

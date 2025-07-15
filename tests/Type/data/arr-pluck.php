<?php

declare(strict_types=1);

namespace EloquentBuilderPluck;

use App\User;
use Illuminate\Support\Arr;

use function PHPStan\Testing\assertType;

/**
 * @param  array<int, array{id: int, name: string}>  $array
 * @param  iterable<array{user: array{id: int, name: string}}>  $iterable
 * @param  list<array{user: User}>  $list
 */
function test(
    array $array,
    iterable $iterable,
    array $list,
): void {
    assertType('array<int, string>', Arr::pluck($array, 'name'));
    assertType('array<string, string>', Arr::pluck($array, 'name', 'name'));

    assertType('array<int, string>', Arr::pluck($iterable, 'user.name'));
    assertType('array<string, string>', Arr::pluck($iterable, 'user.name', 'user.name'));

    assertType('array<int, string>', Arr::pluck($list, 'user.name'));
    assertType('array<string, string>', Arr::pluck($list, 'user.name', 'user.name'));
    assertType('array<int, string>', Arr::pluck($list, ['user', 'name']));
    assertType('array<string, string>', Arr::pluck($list, ['user', 'name'], ['user', 'name']));
}

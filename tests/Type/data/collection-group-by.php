<?php

namespace CollectionGroupBy;

use App\User;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

/**
 * @param Collection $untyped
 * @param Collection<int, User> $users
 * @param Collection<string, User> $stringUsers
 * @param Collection<string, string> $stringStrings
 * @param 'id'|'name' $key
 * @param bool $preserve
 * @param array<int, string> $keys
 */
function test(
    Collection $untyped,
    Collection $users,
    Collection $stringUsers,
    Collection $stringStrings,
    string $key,
    bool $preserve,
    array $keys
): void {
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, mixed>>',
        $untyped->groupBy('id')
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, mixed>>',
        $untyped->groupBy(['id'])
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $untyped->groupBy('id', $preserve)
    );
    assertType(
        'Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, App\User>>',
        $users->groupBy('id')
    );
    assertType(
        'Illuminate\Support\Collection<int|string, Illuminate\Support\Collection<int, App\User>>',
        $users->groupBy($key)
    );
    assertType(
        'Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, Illuminate\Support\Collection<int, App\User>>>',
        $users->groupBy(['id', 'name'])
    );
    assertType(
        'Illuminate\Support\Collection<int, Illuminate\Support\Collection<int<0, 1>, Illuminate\Support\Collection<int, App\User>>>',
        $users->groupBy(['id', fn ($user) => rand(0, 1)])
    );
    assertType(
        'Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, App\User>>',
        $stringUsers->groupBy('id', true)
    );
    assertType(
        'Illuminate\Support\Collection<int, Illuminate\Support\Collection<int|string, App\User>>',
        $stringUsers->groupBy('id', $preserve)
    );
    assertType(
        'Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, Illuminate\Support\Collection<int|string, App\User>>>',
        $stringUsers->groupBy(['id', 'name'], $preserve)
    );
    assertType(
        "Illuminate\Support\Collection<'', Illuminate\Support\Collection<int, string>>",
        $stringStrings->groupBy(['id'])
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $users->groupBy($keys)
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), App\User>>',
        $users->groupBy([])
    );
}

/**
 * @param ?Collection<int, string> $collection
 */
function testNullable(?Collection $collection): void
{
    $res = $collection->groupBy('key');

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), string>>',
        $res
    );
}

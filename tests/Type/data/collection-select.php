<?php

namespace CollectionSelect;

use function PHPStan\Testing\assertType;

function test(): void
{
    $users = collect([
        ['name' => 'Taylor Otwell', 'role' => 'Developer', 'status' => 'active'],
        ['name' => 'Neil Carlo Sucuangco', 'role' => 'Researcher', 'status' => 'active'],
    ]);

    assertType('Illuminate\Support\Collection<int, array{name: string, role: string, status: string}>', $users);
    assertType('Illuminate\Support\Collection<int, array{name: string, role: string, status: string}>', $users->select(['name', 'role']));

    assertType('Illuminate\Support\Collection<int, array{name: string, role: string, status: string}>', $users->select('name'));

    assertType('Illuminate\Support\Collection<int, array{name: string, role: string, status: string}>', $users->select(null));

    $numbers = collect([
        [0 => 'a', 1 => 'b', 2 => 'c'],
        [0 => 'd', 1 => 'e', 2 => 'f'],
    ]);

    assertType('Illuminate\Support\Collection<int, array{string, string, string}>', $numbers);
    assertType('Illuminate\Support\Collection<int, array{string, string, string}>', $numbers->select([0, 2]));

    $mixed = collect([
        [0 => 'a', 'name' => 'John', 'age' => 30],
        [0 => 'b', 'name' => 'Jane', 'age' => 25],
    ]);

    assertType('Illuminate\Support\Collection<int, array{0: string, name: string, age: int}>', $mixed);
    assertType('Illuminate\Support\Collection<int, array{0: string, name: string, age: int}>', $mixed->select([0, 'name']));

    $keys = collect(['name', 'role']);
    assertType('Illuminate\Support\Collection<int, array{name: string, role: string, status: string}>', $users->select($keys));
}


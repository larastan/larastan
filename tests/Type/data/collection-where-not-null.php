<?php

namespace CollectionWhereNotNull;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;

use function PHPStan\Testing\assertType;

/** @param EloquentCollection<int, ?\App\User> $foo */
function test(EloquentCollection $foo): void
{
    assertType('Illuminate\Support\Collection<int, int|string>', collect([1, 2, null, '', 'hello'])->whereNotNull());
    assertType('Illuminate\Support\Collection<int, int|string>', collect([1, 2, null, '', 'hello'])->whereNotNull());
    assertType('Illuminate\Support\Collection<int, int|string>', collect([1, 2, null, '', 'hello'])->whereNotNull(null));
    assertType(
        'Illuminate\Support\Collection<int, App\User|array{id: string}|stdClass>',
        collect([new \App\User, new \stdClass, ['id' => 'foo'], 'foo', true, 22])->whereNotNull('id')
    );
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->whereNotNull('blocked'));
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->whereNotNull('blocked'));
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->whereNotNull());
}

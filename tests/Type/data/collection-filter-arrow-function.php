<?php

namespace CollectionFilter;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Support\Collection<int, int<3, max>>', collect([1, 2, 3, 4, 5, 6])->filter(fn (int $value) => $value > 2));

/** @param EloquentCollection<int, User> $foo */
function bar(Collection $foo): void
{
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->filter(fn (User $user) => ! $user->blocked));
}

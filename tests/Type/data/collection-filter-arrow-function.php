<?php

namespace CollectionFilter;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

use function PHPStan\Testing\assertType;

/** @param EloquentCollection<int, User> $users */
function test(EloquentCollection $users): void
{
    assertType('Illuminate\Support\Collection<int, int<3, max>>', collect([1, 2, 3, 4, 5, 6])->filter(fn (int $value) => $value > 2));
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $users->filter(fn (User $user) => ! $user->blocked));
}

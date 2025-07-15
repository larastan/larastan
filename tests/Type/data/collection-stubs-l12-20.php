<?php

namespace CollectionStubsL1220;

use App\User;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\Support\Collection<int, non-falsy-string>', User::get()->pluck(fn (User $user) => "$user->id - $user->email", 'id'));
    assertType('Illuminate\Support\Collection<non-falsy-string, int>', User::get()->pluck('id', fn (User $user) => "$user->id - $user->email"));
    assertType('Illuminate\Support\Collection<non-falsy-string, string>', User::get()->pluck(fn (User $user) => $user->email, fn (User $user) => "$user->id - $user->email"));
}

<?php

namespace CollectionNullsafeChain;

use App\User;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

class Holder
{
    /** @param Collection<int, User|null> $users */
    public function __construct(public Collection $users)
    {
    }
}

function test(?Holder $holder): void
{
    assertType('Illuminate\Support\Collection<int, App\User>|null', $holder?->users->filter(fn (?User $user) => $user instanceof User));
    assertType('Illuminate\Support\Collection<int, App\User>|null', $holder?->users->filter(function (?User $user) {
        return $user instanceof User;
    }));
    assertType('Illuminate\Support\Collection<int, App\User>|null', $holder?->users->filter());
    assertType('Illuminate\Support\Collection<int, App\User>|null', $holder?->users->reject(fn (?User $user) => $user === null));
    assertType('Illuminate\Support\Collection<int, App\User>|null', $holder?->users->whereNotNull());
    assertType('Illuminate\Support\Collection<int, App\User>|null', $holder?->users->values()->filter(fn (?User $user) => $user instanceof User));
}

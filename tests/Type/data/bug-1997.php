<?php

namespace Bug1997;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use function PHPStan\Testing\assertType;

interface User
{
    /** @return BelongsToMany<Team> */
    public function teams(): BelongsToMany;

    public function getKey();
}

interface Team {}


function test(User $user): void
{
    assertType('*ERROR*', $user->teams()->whereKey($user->getKey()));
}

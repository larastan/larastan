<?php

namespace GateFacade;

use App\User;
use Illuminate\Support\Facades\Gate;
use function PHPStan\Testing\assertType;

function foo(): void
{
    assertType('Illuminate\Auth\Access\Gate', Gate::forUser(new User()));
    assertType('Illuminate\Auth\Access\Response', Gate::inspect('foo'));
    assertType('bool', Gate::has('foo'));
}

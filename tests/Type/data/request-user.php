<?php

declare(strict_types=1);

namespace RequestObject;

use Illuminate\Http\Request;

use function PHPStan\Testing\assertType;

function test(Request $request): void
{
    assertType('App\Admin|App\User|null', $request->user());
    assertType('App\User|null', $request->user('web'));
    assertType('App\Admin|null', $request->user('admin'));
}

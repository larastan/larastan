<?php

namespace Auth;

use App\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('App\Admin|App\User|null', Auth::user());
    assertType('bool', Auth::check());
    assertType('int|string|null', Auth::id());
    assertType('null', Auth::guard()->logout());
    assertType(SessionGuard::class, Auth::guard('web'));
    assertType('App\User|null', Auth::guard('web')->user());
    assertType('App\Admin|null', Auth::guard('admin')->user());
    assertType(TokenGuard::class, Auth::guard('api'));
    assertType(SessionGuard::class, Auth::guard('undefined'));
    assertType('null', Auth::guard()->login($user));
    assertType('App\Admin|App\User', Auth::authenticate());
}

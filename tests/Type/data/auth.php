<?php

namespace Auth;

use App\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('bool', Auth::check());

    assertType('int|string|null', Auth::id());
    assertType('App\Admin|App\User', Auth::authenticate());
    assertType('null', Auth::guard()->logout());
    assertType('null', Auth::guard()->login($user));

    assertType(SessionGuard::class, Auth::guard('web'));
    assertType('int|string|null', Auth::guard('web')->id());
    assertType('App\User|null', Auth::guard('web')->user());
    assertType('App\User', Auth::guard('web')->authenticate());
    assertType('null', Auth::guard('web')->logout());

    assertType(SessionGuard::class, Auth::guard('admin'));
    assertType('int|string|null', Auth::guard('admin')->id());
    assertType('App\Admin|null', Auth::guard('admin')->user());
    assertType('App\Admin', Auth::guard('admin')->authenticate());

    assertType(TokenGuard::class, Auth::guard('api'));
    assertType('int|string|null', Auth::guard('api')->id());
    assertType('App\User|null', Auth::guard('api')->user());
    assertType('App\User', Auth::guard('api')->authenticate());

    assertType(StatefulGuard::class, Auth::guard('unknown'));
    assertType('int|string|null', Auth::guard('unknown')->id());
    assertType(Authenticatable::class . '|null', Auth::guard('unknown')->user());
}

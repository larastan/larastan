<?php

namespace Auth;

use App\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use function PHPStan\Testing\assertType;

class AuthExtension
{
    public function testUser(): void
    {
        assertType('App\User|null', Auth::user());
    }

    public function testCheck(): void
    {
        assertType('bool', Auth::check());
    }

    public function testId(): void
    {
        assertType('int|string|null', Auth::id());
    }

    public function testLogout(): void
    {
        assertType('void', Auth::guard()->logout());
    }

    public function testGuard(): void
    {
        assertType(StatefulGuard::class, Auth::guard('web'));
    }

    public function testGuardUser(): void
    {
        assertType('App\User|null', Auth::guard('web')->user());
    }

    public function testGuardAdminUser(): void
    {
        assertType('App\Admin|null', Auth::guard('admin')->user());
    }

    public function testSessionGuard(): void
    {
        assertType(SessionGuard::class, Auth::guard('session'));
    }

    public function testLogin(User $user): void
    {
        assertType('void', Auth::guard()->login($user));
    }
}

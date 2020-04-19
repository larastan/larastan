<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;

class AuthExtension
{
    public function testUser(): ?User
    {
        return Auth::user();
    }

    public function testCheck(): bool
    {
        return Auth::check();
    }

    /**
     * @return int|string|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testId()
    {
        return Auth::id();
    }

    public function testLogout(): void
    {
        Auth::guard()->logout();
    }

    public function testSessionGuard(): SessionGuard
    {
        return Auth::guard('session');
    }

    public function testLogin(User $user): void
    {
        Auth::guard()->login($user);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\Admin;
use App\User;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;

class AuthExtension
{
    public function testAuth(): Factory
    {
        return auth();
    }

    public function testAuthGuard(): Guard
    {
        return auth()->guard('web');
    }

    public function testAuthGuardParameter(): Guard
    {
        return auth('web');
    }

    public function testUser(): ?User
    {
        return auth()->user();
    }

    public function testCheck(): bool
    {
        return auth()->check();
    }

    public function testAuthGuardUser(): ?User
    {
        return auth()->guard('web')->user();
    }

    public function testAuthGuardParameterUser(): ?User
    {
        return auth('web')->user();
    }

    public function testAuthGuardAdminUser(): ?Admin
    {
        return auth()->guard('admin')->user();
    }

    public function testAuthGuardAdminParameterUser(): ?Admin
    {
        return auth('admin')->user();
    }

    /**
     * @return int|string|null
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testId()
    {
        return auth()->id();
    }

    /** @return \Illuminate\Contracts\Auth\Authenticatable|false */
    public function testLoginUsingId(User $user)
    {
        return auth()->loginUsingId($user->id);
    }

    public function testLogin(User $user): void
    {
        auth()->login($user);
    }
}

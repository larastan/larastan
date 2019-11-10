<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

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

    /**
     * @return int|string|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testId()
    {
        return auth()->id();
    }
}

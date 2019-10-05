<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Factory;

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

    public function testId(): ?int
    {
        return auth()->id();
    }
}

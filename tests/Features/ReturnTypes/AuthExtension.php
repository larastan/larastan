<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
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

    public function testId(): ?int
    {
        return Auth::id();
    }
}

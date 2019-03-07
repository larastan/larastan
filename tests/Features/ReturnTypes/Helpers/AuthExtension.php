<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

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
}

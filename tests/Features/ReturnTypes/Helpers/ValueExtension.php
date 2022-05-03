<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\User;

class ValueExtension
{
    public function testClosure(): ?User
    {
        return value(function (): ?User {
            return User::first();
        });
    }

    public function testInt(): int
    {
        // @phpstan-ignore-line
        return value(5);
    }
}

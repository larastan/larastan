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
        return value(5); // @phpstan-ignore-line as this violates the NoUselessValueFunctionCallsRule but is still usefull to test
    }
}

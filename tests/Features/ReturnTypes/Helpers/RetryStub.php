<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\User;

class RetryStub
{
    public function testRetryVoid(): void
    {
        retry(1, function () {
        });
    }

    public function testRetryInt(): int
    {
        return retry(5, function (): int {
            return 5;
        });
    }

    public function testRetryWhen(): ?User
    {
        return retry(5, function (): ?User {
            return User::first();
        }, 0, function (): bool {
            return true;
        });
    }

    public function testRetryWhenException(): bool
    {
        return retry(5, function (int $attempt): bool {
            return false;
        }, 0, function (\Exception $e): bool {
            return true;
        });
    }
}

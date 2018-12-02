<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;

class LengthAwarePaginatorExtension
{
    public function testCollection(): array
    {
        return User::paginate()->all();
    }
}

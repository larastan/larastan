<?php

declare(strict_types=1);

namespace Tests\Features\Laravel8\Methods;

use App\User;

class ModelExtension
{
    public function testSole(): User
    {
        return User::sole();
    }
}

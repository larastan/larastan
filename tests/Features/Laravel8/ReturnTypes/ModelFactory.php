<?php

declare(strict_types=1);

namespace Tests\Features\Laravel8\ReturnTypes;

use Laravel8\Models\User;

class ModelFactory
{
    public function testCreateOne(): User
    {
        return User::factory()->createOne();
    }
}

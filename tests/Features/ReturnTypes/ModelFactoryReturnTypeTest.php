<?php

namespace Tests\Features\ReturnTypes;

use App\User;
use Tests\Application\database\factories\UserFactory;

class ModelFactoryReturnTypeTest
{
    public function testFactoryCallFromModelReturnsSpecificFactory(): UserFactory
    {
        return User::factory();
    }
}

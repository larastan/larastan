<?php

namespace Tests\Features\ReturnTypes;

use App\User;
use Tests\Application\database\factories\UserFactory;

class ModelFactoryReturnTypeTest
{
    /** @phpstan-return UserFactory */
    public function testFactoryCallFromModelReturnsSpecificFactory()
    {
        return User::factory();
    }
}

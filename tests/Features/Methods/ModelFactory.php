<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Database\Factories\UserFactory;

class ModelFactory
{
    public function testHasRelation(): UserFactory
    {
        return User::factory()->hasPosts();
    }

    public function testHasRelationWithCount(): UserFactory
    {
        return User::factory()->hasPosts(3);
    }

    public function testHasRelationWithState(): UserFactory
    {
        return User::factory()->hasPosts(['active' => 0]);
    }

    public function testHasRelationWithCountAndState(): User
    {
        return User::factory()->hasPosts(3, ['active' => 1])->createOne();
    }

    public function testForRelation(): UserFactory
    {
        return User::factory()->forParent();
    }

    public function testForRelationWithState(): UserFactory
    {
        return User::factory()->forParent(['meta' => ['foo']]);
    }
}

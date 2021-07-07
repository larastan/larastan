<?php

declare(strict_types=1);

namespace Tests\Features\Laravel8\Methods;

use Database\Factories\UserFactory;
use Laravel8\Models\User;

class ModelFactory
{
    public function testHasRelation(): UserFactory
    {
        return User::factory()->hasAccounts();
    }

    public function testHasRelationWithCount(): UserFactory
    {
        return User::factory()->hasAccounts(3);
    }

    public function testHasRelationWithState(): UserFactory
    {
        return User::factory()->hasAccounts(['active' => 0]);
    }

    public function testHasRelationWithCountAndState(): User
    {
        return User::factory()->hasAccounts(3, ['active' => 1])->createOne();
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

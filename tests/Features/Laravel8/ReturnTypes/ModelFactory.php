<?php

declare(strict_types=1);

namespace Tests\Features\Laravel8\ReturnTypes;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Laravel8\Models\User;

class ModelFactory
{
    public function testFactory(): UserFactory
    {
        return User::factory();
    }

    public function testNew(): UserFactory
    {
        return User::factory()->new();
    }

    public function testCreateOne(): User
    {
        return User::factory()->createOne();
    }

    /** @phpstan-return Collection<User> */
    public function testCreateMany(): Collection
    {
        return User::factory()->createMany([]);
    }

    /** @phpstan-return Collection<User>|User */
    public function testCreate()
    {
        return User::factory()->create();
    }

    public function testMakeOne(): User
    {
        return User::factory()->makeOne();
    }

    /** @phpstan-return Collection<User>|User */
    public function testMake()
    {
        return User::factory()->make();
    }
}

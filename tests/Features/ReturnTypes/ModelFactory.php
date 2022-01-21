<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\Post;
use App\User;
use Database\Factories\PostFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;

class ModelFactory
{
    public function testFactoryWithParentModelUsingFactory(): UserFactory
    {
        return User::factory();
    }

    public function testNewWithParentModelUsingFactory(): UserFactory
    {
        return User::factory()->new();
    }

    public function testCreateOneWithParentModelUsingFactory(): User
    {
        return User::factory()->createOne();
    }

    /** @phpstan-return Collection<int, User> */
    public function testCreateManyWithParentModelUsingFactory(): Collection
    {
        return User::factory()->createMany([]);
    }

    /** @phpstan-return Collection<int, User>|User */
    public function testCreateWithParentModelUsingFactory()
    {
        return User::factory()->create();
    }

    public function testMakeOneWithParentModelUsingFactory(): User
    {
        return User::factory()->makeOne();
    }

    /** @phpstan-return Collection<int, User>|User */
    public function testMakeWithParentModelUsingFactory()
    {
        return User::factory()->make();
    }

    public function testStateWithParentModelUsingFactory(): UserFactory
    {
        return User::factory()->unverified();
    }

    public function testFactory(): PostFactory
    {
        return Post::factory();
    }

    public function testNew(): PostFactory
    {
        return Post::factory()->new();
    }

    public function testCreateOne(): Post
    {
        return Post::factory()->createOne();
    }

    /** @phpstan-return Collection<int, Post> */
    public function testCreateMany(): Collection
    {
        return Post::factory()->createMany([]);
    }

    /** @phpstan-return Collection<int, Post>|Post */
    public function testCreate()
    {
        return Post::factory()->create();
    }

    public function testMakeOne(): Post
    {
        return Post::factory()->makeOne();
    }

    /** @phpstan-return Collection<int, Post>|Post */
    public function testMake()
    {
        return Post::factory()->make();
    }
}

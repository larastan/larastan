<?php declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\User;
use Illuminate\Database\Eloquent\Collection;

class FactoryHelper
{
    /** @phpstan-return Collection<User> */
    public function testItReturnsCollection() : Collection
    {
        return factory(User::class, 5)->create();
    }

    /** @phpstan-return Collection<User> */
    public function testItReturnsCollectionWithZero() : Collection
    {
        return factory(User::class, 0)->create();
    }

    public function testItReturnsModel() : User
    {
        return factory(User::class)->create();
    }

    public function testItReturnsModelWithNull() : User
    {
        return factory(User::class, null)->create();
    }
}

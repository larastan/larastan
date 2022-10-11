<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CorrectCollectionCalls
{
    public function staticCount(): int
    {
        return User::count();
    }

    public function hydrate(): ?User
    {
        $users = [['name' => 'Daan', 'email' => 'test@test.dev']];

        return User::hydrate($users)->first();
    }

    /** @return Collection<int, mixed> */
    public function pluckQuery(): Collection
    {
        return User::query()->pluck('id');
    }

    /** @return Collection<int, mixed> */
    public function pluckComputed(): Collection
    {
        return User::all()->pluck('allCapsName');
    }

    /** @return Collection<int, mixed> */
    public function pluckRelation(): Collection
    {
        return User::with(['accounts'])->get()->pluck('accounts');
    }

    public function firstRelation(): ?Account
    {
        return User::firstOrFail()->accounts()->first();
    }

    /** @phpstan-return mixed */
    public function maxQuery()
    {
        return DB::table('users')->max('id');
    }

    /** @phpstan-return mixed */
    public function collectionCalls()
    {
        return collect([1, 2, 3])->flip()->reverse()->sum();
    }

    /**
     * Can't analyze the closure as a parameter to contains, so should not throw any error.
     *
     * @return bool
     */
    public function testContainsClosure(): bool
    {
        return User::where('id', '>', 1)->get()->contains(function (User $user): bool {
            return $user->id === 2;
        });
    }

    /**
     * Can't analyze the closure as a parameter to first, so should not throw any error.
     *
     * @return User|null
     */
    public function testFirstClosure(): ?User
    {
        return User::where('id', '>', 1)->get()->first(function (User $user): bool {
            return $user->id === 2;
        });
    }

    /** @phpstan-return mixed */
    public function testAggregateNoArgs()
    {
        return User::query()
            ->select([DB::raw('COUNT(*) as temp')])
            ->pluck('temp')
            ->sum();
    }

    /** @phpstan-return mixed */
    public function testRelationAggregate(User $user)
    {
        return $user->group()
            ->withCount(['accounts' => function ($query) {
                $query->where('id', '<>', 1);
            }])
            ->pluck('id')
            ->avg();
    }
}

class Foo extends Model
{
    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return FooBuilder
     */
    public function newEloquentBuilder($query): FooBuilder
    {
        return new FooBuilder($query);
    }
}

/**
 * @extends Builder<Foo>
 */
class FooBuilder extends Builder
{
    /**
     * @return mixed
     */
    public function returnMixed()
    {
        /** @var mixed */
        return $this;
    }
}

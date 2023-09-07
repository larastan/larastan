<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\AccountCollection;
use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnnecessaryCollectionCallsEloquent
{
    /** @return Collection<int, mixed> */
    public function pluckId(): Collection
    {
        return User::all()->pluck('id');
    }

    public function testCallCountWrong(): int
    {
        return User::where('id', '>', 5)->get()->count();
    }

    /** @return Collection<int, mixed> */
    public function testCallGetPluckWrong(): Collection
    {
        return User::query()->get()->pluck('id');
    }

    public function testCallCountWrongly(): int
    {
        return User::all()->count();
    }

    public function testCallFirstWrongly(): ?User
    {
        return User::all()->first();
    }

    /** @return AccountCollection<int, \App\Account> */
    public function testCallRelationTakeWrongly(): AccountCollection
    {
        return User::firstOrFail()->accounts()->get()->take(2);
    }

    public function testDbQueryBuilder(): int
    {
        return DB::table('users')->get()->count();
    }

    public function testVarWrong(): bool
    {
        $query = User::query()->limit(3)->where('email', 'foo@bar.com');

        return $query->get()->isEmpty();
    }

    public function testVarWrongFirst(): ?User
    {
        return User::where('id', 1)->get()->first();
    }

    public function testContainsWrong(): bool
    {
        return User::all()->contains(User::firstOrFail());
    }

    public function testPluckCountWrong(): int
    {
        return User::query()->pluck('id')->count();
    }

    /** @return EloquentCollection<int, User> */
    public function testCallWhereWrong(): EloquentCollection
    {
        return User::all()->where('id', '<', 4);
    }

    /** @return EloquentCollection<int, User> */
    public function testCallDiffWrong(): EloquentCollection
    {
        return User::all()->diff([new User]);
    }

    /**
     * @return int[]
     */
    public function testCallModelKeysWrong(): array
    {
        return User::all()->modelKeys();
    }

    public function testContainsStrictWrong(): bool
    {
        return User::query()->get()->containsStrict('id');
    }

    /** @phpstan-return mixed */
    public function testSum()
    {
        return User::pluck('id')->sum();
    }
}

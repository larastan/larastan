<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BuilderExtension
{
    public function testArrayOfWheres(): Collection
    {
        return User::where([
            ['active', true],
            ['id', '>=', 5],
            ['id', '<=', 10],
        ])->get();
    }

    /** @return Collection<User> */
    public function testCallingGetOnModelWithStaticQueryBuilder(): Collection
    {
        return User::where('id', 1)->get();
    }

    /** @return Collection<User> */
    public function testCallingGetOnModelWithVariableQueryBuilder(): Collection
    {
        return (new User)->where('id', 1)->get();
    }

    /** @return Collection<User> */
    public function testCallingLongGetChainOnModelWithStaticQueryBuilder(): Collection
    {
        return User::where('id', 1)
            ->whereNotNull('name')
            ->where('email', 'bar')
            ->whereFoo(['bar'])
            ->get();
    }

    /** @return Collection<User> */
    public function testCallingLongGetChainOnModelWithVariableQueryBuilder(): Collection
    {
        return (new User)->whereNotNull('name')
            ->where('email', 'bar')
            ->whereFoo(['bar'])
            ->get();
    }

    /** @return Collection<User> */
    public function testCallingGetOnModelWithVariableQueryBuilder2(): Collection
    {
        $user = new User;

        return $user->where('email', 1)->get();
    }

    /** @return Collection<User> */
    public function testUsingCollectionMethodsAfterGet(): Collection
    {
        return User::whereIn('id', [1, 2, 3])->get()->mapWithKeys(function ($user) {
            return [$user->name => $user->email];
        });
    }

    public function testCallingQueryBuilderMethodOnEloquentBuilderReturnsEloquentBuilder(Builder $builder): Builder
    {
        return $builder->whereNotNull('test');
    }

    /** @phpstan-return mixed */
    public function testMax()
    {
        $user = new User;

        return $user->where('email', 1)->max('email');
    }

    public function testExists(): bool
    {
        $user = new User;

        return $user->where('email', 1)->exists();
    }

    /**
     * @phpstan-return Builder<User>
     */
    public function testWith(): Builder
    {
        return User::with('email')->whereNull('name');
    }

    /**
     * @phpstan-return Builder<User>
     */
    public function testWithWithBuilderMethods(): Builder
    {
        return User::with('email')
            ->where('email', 'bar')
            ->orWhere('name', 'baz');
    }

    public function testFindWithInteger(): ?User
    {
        return User::with(['email'])->find(1);
    }

    /**
     * @return Collection<User>|null
     */
    public function testFindWithArray()
    {
        return User::with(['email'])->find([1, 2, 3]);
    }

    public function testFindOrFailWithInteger(): User
    {
        return User::with(['email'])->findOrFail(1);
    }

    /**
     * @return Collection<User>
     */
    public function testFindOrFailWithArray()
    {
        return User::with(['email'])->findOrFail([1, 2, 3]);
    }

    public function testFindOrNewWithInteger(): User
    {
        return User::with(['email'])->findOrNew(1);
    }

    /**
     * @return mixed
     */
    public function testFindWithCustom3rdPartyBuilder()
    {
        return (new CustomBuilder(User::query()->getQuery()))->with('email')->find(1);
    }

    /**
     * @return Collection<User>
     */
    public function testFindOrNewWithArray()
    {
        return User::with(['email'])->findOrNew([1, 2, 3]);
    }

    /**
     * @phpstan-return Collection<User>
     */
    public function testHydrate(): Collection
    {
        return User::hydrate([]);
    }

    /**
     * @phpstan-return Collection<User>
     */
    public function testFromQuery(): Collection
    {
        return User::fromQuery('SELECT * FROM users');
    }
}

/**
 * @property string $email
 */
class TestModel extends Model
{
    /** @return Collection|TestModel[] */
    public function testCallingGetInsideModel(): Collection
    {
        return $this->where('email', 1)->get();
    }

    /** @phpstan-return Builder<TestModel> */
    public function testStaticQuery(): Builder
    {
        return static::query()->where('email', 'bar');
    }

    /** @phpstan-return Builder<TestModel> */
    public function testQuery(): Builder
    {
        return $this->where('email', 'bar');
    }
}

class CustomBuilder extends Builder
{
}

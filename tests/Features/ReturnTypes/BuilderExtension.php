<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BuilderExtension
{
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
            ->whereNotNull('active')
            ->where('foo', 'bar')
            ->whereFoo(['bar'])
            ->get();
    }

    /** @return Collection<User> */
    public function testCallingLongGetChainOnModelWithVariableQueryBuilder(): Collection
    {
        return (new User)->whereNotNull('active')
            ->where('foo', 'bar')
            ->whereFoo(['bar'])
            ->get();
    }

    /** @return Collection<User> */
    public function testCallingGetOnModelWithVariableQueryBuilder2(): Collection
    {
        $user = new User;

        return $user->where('test', 1)->get();
    }

    /** @return Collection<User> */
    public function testUsingCollectionMethodsAfterGet(): Collection
    {
        return User::whereIn('id', [1, 2, 3])->get()->mapWithKeys('key');
    }

    public function testCallingQueryBuilderMethodOnEloquentBuilderReturnsEloquentBuilder(Builder $builder): Builder
    {
        return $builder->whereNotNull('test');
    }

    public function testMax(): int
    {
        $user = new User;

        return (int) $user->where('test', 1)->max('foo');
    }

    public function testExists(): bool
    {
        $user = new User;

        return $user->where('test', 1)->exists();
    }

    /**
     * @phpstan-return Builder<User>
     */
    public function testWith(): Builder
    {
        return User::with('foo')->whereNull('bar');
    }

    /**
     * @phpstan-return Builder<User>
     */
    public function testWithWithBuilderMethods(): Builder
    {
        return User::with('foo')
            ->where('foo', 'bar')
            ->orWhere('bar', 'baz');
    }

    public function testFindWithInteger(): ?User
    {
        return User::with(['foo'])->find(1);
    }

    /**
     * @return Collection<User>|null
     */
    public function testFindWithArray()
    {
        return User::with(['foo'])->find([1, 2, 3]);
    }

    public function testFindOrFailWithInteger(): User
    {
        return User::with(['foo'])->findOrFail(1);
    }

    /**
     * @return Collection<User>
     */
    public function testFindOrFailWithArray()
    {
        return User::with(['foo'])->findOrFail([1, 2, 3]);
    }

    public function testFindOrNewWithInteger(): User
    {
        return User::with(['foo'])->findOrNew(1);
    }

    /**
     * @return mixed
     */
    public function testFindWithCustom3rdPartyBuilder()
    {
        return (new CustomBuilder(User::query()->getQuery()))->with('foo')->find(1);
    }

    /**
     * @return Collection<User>
     */
    public function testFindOrNewWithArray()
    {
        return User::with(['foo'])->findOrNew([1, 2, 3]);
    }
}

class TestModel extends Model
{
    /** @return Collection|TestModel[] */
    public function testCallingGetInsideModel(): Collection
    {
        return $this->where('test', 1)->get();
    }

    /** @phpstan-return Builder<TestModel> */
    public function testStaticQuery(): Builder
    {
        return static::query()->where('foo', 'bar');
    }

    /** @phpstan-return Builder<TestModel> */
    public function testQuery(): Builder
    {
        return $this->where('foo', 'bar');
    }
}

class CustomBuilder extends Builder
{
}

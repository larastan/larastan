<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BuilderExtension
{
    /** @return Collection|User[] */
    public function testCallingGetOnModelWithStaticQueryBuilder(): Collection
    {
        return User::where('id', 1)->get();
    }

    /** @return Collection|User[] */
    public function testCallingGetOnModelWithVariableQueryBuilder(): Collection
    {
        return (new User)->where('id', 1)->get();
    }

    /** @return Collection|User[] */
    public function testCallingLongGetChainOnModelWithStaticQueryBuilder(): Collection
    {
        return User::where('id', 1)
            ->whereNotNull('active')
            ->where('foo', 'bar')
            ->whereFoo(['bar'])
            ->get();
    }

    /** @return Collection|User[] */
    public function testCallingLongGetChainOnModelWithVariableQueryBuilder(): Collection
    {
        return (new User)->whereNotNull('active')
            ->where('foo', 'bar')
            ->whereFoo(['bar'])
            ->get();
    }

    /** @return Collection|User[] */
    public function testCallingGetOnModelWithVariableQueryBuilder2(): Collection
    {
        $user = new User;

        return $user->where('test', 1)->get();
    }

    /** @return Collection|User[] */
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

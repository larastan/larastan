<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class ModelExtension
{
    public function testFind(): ?User
    {
        return User::find(1);
    }

    public function testFindOnGenericModel(Model $model): ?Model
    {
        return $model::find(1);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function testFindOnModelClassString(string $modelClass): ?Model
    {
        return $modelClass::find(1);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testFindCanReturnCollection(): Collection
    {
        return User::find([1, 2, 3]);
    }

    /** @phpstan-return Collection<int, User>|null */
    public function testFindMany()
    {
        return User::findMany([1, 2, 3]);
    }

    public function testFindOrFail(): User
    {
        return User::findOrFail(1);
    }

    /**
     * @phpstan-return Collection<int, \App\User>
     */
    public function testFindOrFailCanReturnCollection(): Collection
    {
        /** @var Collection<int, \App\User> $users */
        $users = User::findOrFail([1, 2, 3]);

        return $users;
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testChainingCollectionMethodsOnModel(): Collection
    {
        return User::findOrFail([1, 2, 3])->makeHidden('foo');
    }

    public function testCollectionMethodWillReturnUser(): ?User
    {
        return User::findOrFail([1, 2, 3])->makeHidden('foo')->first();
    }

    /** @phpstan-return Collection<int, User>|null */
    public function testFindWithCastingToArray(FormRequest $request): ?Collection
    {
        /** @var array<string, string> $requestData */
        $requestData = $request->validated();

        return User::find((array) $requestData['user_ids']);
    }

    public function testFindWithCastingToInt(): ?User
    {
        return User::find((int) '1');
    }
}

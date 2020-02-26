<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\Account;
use App\Group;
use App\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Relations
{
    public function testRelationWhere(): HasMany
    {
        return (new User())->accounts()->where('foo', 'bar');
    }

    public function testRelationWhereIn(): HasMany
    {
        return (new User())->accounts()->whereIn('id', [1, 2, 3]);
    }

    public function testRelationDynamicWhere(): HasMany
    {
        return (new User())->accounts()->whereFoo(['bar']);
    }

    public function testCreateWithRelation(): Account
    {
        /** @var User $user */
        $user = User::first();

        return $user->accounts()->create();
    }

    public function testCustomRelationCreate(): Account
    {
        /** @var User $user */
        $user = User::first();

        return $user->syncableRelation()->create();
    }

    public function testCreateWithGettingModelFromMethod(): Account
    {
        return $this->getUser()->accounts()->create();
    }

    public function testFirstWithRelation(): ?Account
    {
        return (new User())->accounts()->where('foo', 'bar')->first();
    }

    public function testIncrementOnRelation(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->accounts()->increment('counter');
    }

    public function testDecrementOnRelation(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->accounts()->decrement('counter');
    }

    public function testIncrementWithAmountOnRelation(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->accounts()->increment('counter', 5);
    }

    public function testDecrementWithAmountOnRelation(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->accounts()->decrement('counter', 5);
    }

    public function testPaginate(): LengthAwarePaginator
    {
        /** @var User $user */
        $user = new User;

        return $user->accounts()->paginate(5);
    }

    public function testMorph(): MorphTo
    {
        /** @var User $user */
        $user = new User;

        return $user->addressable()->where('foo', 'bar');
    }

    public function testModelScopesOnRelation(): HasMany
    {
        /** @var User $user */
        $user = new User;

        return $user->accounts()->active();
    }

    /**
     * @phpstan-return Collection<Account>
     */
    public function testGetModelScopesOnRelation(): Collection
    {
        /** @var User $user */
        $user = new User;

        return $user->accounts()->active()->get();
    }

    /**
     * @param User $user
     *
     * @return Collection<Account>
     */
    public function testGetOnRelationAndBuilder(User $user): Collection
    {
        /** @var Group $group */
        $group = $user->group;

        return $group->accounts()->where('active', 1)->get();
    }

    public function testMakeOnRelation(User $user): Account
    {
        return $user->accounts()->make();
    }

    private function getUser(): User
    {
        return User::firstOrFail();
    }

    /**
     * @see https://github.com/nunomaduro/larastan/issues/476
     */
    public function testRelationshipPropertyHasCorrectReturnTypeWithIdeHelperDocblocks(): ?Account
    {
        $user = new User();

        return $user->accounts->first();
    }
}

/**
 * @property-read User $relation
 */
class RelationCreateExample extends Model
{
    public function relation(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addRelation(): User
    {
        return $this->relation()->create([]);
    }
}

class ModelWithoutPropertyAnnotation extends Model
{
    public function relation(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addRelation(): User
    {
        return $this->relation()->create([]);
    }
}

class TestRelationCreateOnExistingModel
{
    /** @var User */
    private $user;

    public function testRelationCreateOnExistingModel(): Account
    {
        return $this->user->accounts()->create();
    }
}

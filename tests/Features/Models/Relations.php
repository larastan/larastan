<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\Account;
use App\Group;
use App\Role;
use App\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function testCreateWithRelation(User $user): Account
    {
        return $user->accounts()->create();
    }

    public function testCustomRelationCreate(User $user): Account
    {
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

    public function testIncrementOnRelation(User $user): int
    {
        return $user->accounts()->increment('counter');
    }

    public function testDecrementOnRelation(User $user): int
    {
        return $user->accounts()->decrement('counter');
    }

    public function testIncrementWithAmountOnRelation(User $user): int
    {
        return $user->accounts()->increment('counter', 5);
    }

    public function testDecrementWithAmountOnRelation(User $user): int
    {
        return $user->accounts()->decrement('counter', 5);
    }

    public function testPaginate(User $user): LengthAwarePaginator
    {
        return $user->accounts()->paginate(5);
    }

    public function testMorph(User $user): MorphTo
    {
        return $user->addressable()->where('foo', 'bar');
    }

    public function testModelScopesOnRelation(User $user): HasMany
    {
        return $user->accounts()->active();
    }

    /**
     * @return Collection<Role>
     */
    public function testRelationWithPivot(User $user): Collection
    {
        return $user->roles()->get();
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

    /** @test */
    public function it_doesnt_treat_whereHas_as_dynamic_where(): User
    {
        return User::with('accounts')->whereHas('accounts')->firstOrFail();
    }

    /**
     * @phpstan-return BelongsTo<User, Account>
     */
    public function testRelationWithTrait(Account $account): BelongsTo
    {
        return $account->ownerRelation();
    }

    /**
     * @phpstan-return BelongsTo<Account, Account>
     */
    public function testRelationInTraitWithStaticClass(Account $account): BelongsTo
    {
        return $account->parent();
    }

    /** @phpstan-return HasMany<User> */
    public function testSameClassRelation(User $user): HasMany
    {
        return $user->children();
    }

    /** @phpstan-return BelongsTo<User, User> */
    public function testSameClassRelationWithGetClass(User $user): BelongsTo
    {
        return $user->parent();
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

class Post extends Model
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addUser(User $user): self
    {
        return $this->author()->associate($user);
    }
}

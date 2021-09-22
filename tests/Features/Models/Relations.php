<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\Account;
use App\Group;
use App\Post;
use App\Role;
use App\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use function PHPStan\Testing\assertType;

class Relations
{
    public function testFirstOrCreateWithRelation(User $user): Account
    {
        return $user->accounts()->firstOrCreate([]);
    }

    /** @phpstan-return HasMany<Account> */
    public function testRelationWhere(): HasMany
    {
        return (new User())->accounts()->where('name', 'bar');
    }

    /** @phpstan-return HasMany<Account> */
    public function testRelationWhereIn(): HasMany
    {
        return (new User())->accounts()->whereIn('id', [1, 2, 3]);
    }

    /** @phpstan-return HasMany<Account> */
    public function testRelationDynamicWhere(): HasMany
    {
        return (new User())->accounts()->whereActive(true);
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
        return (new User())->accounts()->where('name', 'bar')->first();
    }

    public function testIncrementOnRelation(User $user): int
    {
        return $user->accounts()->increment('id');
    }

    public function testDecrementOnRelation(User $user): int
    {
        return $user->accounts()->decrement('id');
    }

    public function testIncrementWithAmountOnRelation(User $user): int
    {
        return $user->accounts()->increment('id', 5);
    }

    public function testDecrementWithAmountOnRelation(User $user): int
    {
        return $user->accounts()->decrement('id', 5);
    }

    public function testPaginate(User $user): LengthAwarePaginator
    {
        return $user->accounts()->paginate(5);
    }

    public function testMorph(User $user): MorphTo
    {
        return $user->addressable()->where('name', 'bar');
    }

    /** @phpstan-return HasMany<Account> */
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
     * @param  User  $user
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

    /** @phpstan-return BelongsTo<User, Account> */
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

    public function testFirstWhereWithHasManyRelation(User $user): ?Account
    {
        return $user->accounts()->firstWhere('name', 'bar');
    }

    public function testFirstWhereWithBelongsToRelation(User $user): ?Group
    {
        return $user->group()->firstWhere('name', 'bar');
    }

    /** @phpstan-return BelongsTo<Group, User> */
    public function testWithTrashedWithBelongsToRelation(User $user): BelongsTo
    {
        return $user->group()->withTrashed();
    }

    /** @phpstan-return BelongsTo<Group, User> */
    public function testOnlyTrashedWithBelongsToRelation(User $user): BelongsTo
    {
        return $user->group()->onlyTrashed();
    }

    /** @phpstan-return BelongsTo<Group, User> */
    public function testWithoutTrashedWithBelongsToRelation(User $user): BelongsTo
    {
        return $user->group()->withoutTrashed();
    }

    /**
     * @phpstan-return MorphToMany<Address>
     */
    public function testMorphToManyWithTimestamps(Tag $tag): MorphToMany
    {
        return $tag->addresses();
    }

    /**
     * @phpstan-return MorphToMany<Address>
     */
    public function testMorphToManyWithPivot(Tag $tag): MorphToMany
    {
        return $tag->addresses();
    }

    /** @phpstan-return Builder<User> */
    public function testRelationWithWithOnModel(): Builder
    {
        return User::with([
            'accounts' => function (HasMany $query) {
                return $query->where('foo', 'bar');
            },
        ]);
    }

    public function testBelongsToManyCreateReturnsCorrectModel(User $user): Post
    {
        assertType(Post::class, $user->posts()->create());

        return $user->posts()->create();
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
}

class Tag extends Model
{
    /**
     * @phpstan-return MorphToMany<Address>
     */
    public function addresses(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'taggable')->withTimestamps();
    }

    /**
     * @phpstan-return MorphToMany<Address>
     */
    public function addressesWithPivot(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'taggable')->withPivot('foo');
    }
}

class Address extends Model
{
}

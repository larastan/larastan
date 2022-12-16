<?php

namespace ModelRelations;

use App\Account;
use App\Group;
use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use function PHPStan\Testing\assertType;

function testFirstOrCreateWithRelation(User $user)
{
    assertType('App\Account', $user->accounts()->firstOrCreate([]));
}

function testRelationWhere()
{
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', (new User())->accounts()->where('name', 'bar'));
}

function testRelationWhereIn()
{
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', (new User())->accounts()->whereIn('id', [1, 2, 3]));
}

function testRelationDynamicWhere()
{
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', (new User())->accounts()->whereActive(true));
}

function testCreateWithRelation(User $user)
{
    assertType('App\Account', $user->accounts()->create());
}

function testCustomRelationCreate(User $user)
{
    assertType('App\Account', $user->syncableRelation()->create());
}

function testCreateWithGettingModelFromMethod()
{
    assertType('App\Account', getUser()->accounts()->create());
}

function testFirstWithRelation()
{
    assertType('App\Account|null', (new User())->accounts()->where('name', 'bar')->first());
}

function testIncrementOnRelation(User $user)
{
    assertType('int', $user->accounts()->increment('id'));
}

function testDecrementOnRelation(User $user)
{
    assertType('int', $user->accounts()->decrement('id'));
}

function testIncrementWithAmountOnRelation(User $user)
{
    assertType('int', $user->accounts()->increment('id', 5));
}

function testDecrementWithAmountOnRelation(User $user)
{
    assertType('int', $user->accounts()->decrement('id', 5));
}

function testPaginate(User $user)
{
    assertType('Illuminate\Pagination\LengthAwarePaginator<App\Account>', $user->accounts()->paginate(5));
}

function testMorphTo(\App\Address $address)
{
    assertType(
        'Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, App\Address>',
        $address->addressable()->where('name', 'bar')
    );
}

function testMorphMany(User $user)
{
    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<App\Address>', $user->address()->where('name', 'bar'));
}

function testModelScopesOnRelation(User $user)
{
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', $user->accounts()->active());
}

function testRelationWithPivot(User $user)
{
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->get());
}

function testGetOnRelationAndBuilder(User $user)
{
    /** @var Group $group */
    $group = $user->group;

    assertType('App\AccountCollection<int, App\Account>', $group->accounts()->where('active', 1)->get());
}

function testMakeOnRelation(User $user)
{
    assertType('App\Account', $user->accounts()->make());
}

function getUser(): User
{
    return User::firstOrFail();
}

/**
 * @see https://github.com/nunomaduro/larastan/issues/476
 */
function testRelationshipPropertyHasCorrectReturnTypeWithIdeHelperDocblocks()
{
    $user = new User();

    assertType('App\Account|null', $user->accounts->first());
}

function it_doesnt_treat_whereHas_as_dynamic_where()
{
    assertType('App\User', User::with('accounts')->whereHas('accounts')->firstOrFail());
}

function testRelationWithTrait(Account $account)
{
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, App\Account>', $account->ownerRelation());
}

function testRelationInTraitWithStaticClass(Account $account)
{
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Account, App\Account>', $account->parent());
}

function testSameClassRelation(User $user)
{
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\User>', $user->children());
}

function testSameClassRelationWithGetClass(User $user)
{
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, App\User>', $user->parent());
}

function testFirstWhereWithHasManyRelation(User $user)
{
    assertType('App\Account|null', $user->accounts()->firstWhere('name', 'bar'));
}

function testFirstWhereWithBelongsToRelation(User $user)
{
    assertType('App\Group|null', $user->group()->firstWhere('name', 'bar'));
}

function testWithTrashedWithBelongsToRelation(User $user)
{
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $user->group()->withTrashed());
}

function testOnlyTrashedWithBelongsToRelation(User $user)
{
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $user->group()->onlyTrashed());
}

function testWithoutTrashedWithBelongsToRelation(User $user)
{
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $user->group()->withoutTrashed());
}

function testMorphToManyWithTimestamps(Tag $tag)
{
    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelations\Address>', $tag->addresses());
}

function testMorphToManyWithPivot(Tag $tag)
{
    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelations\Address>', $tag->addresses());
}

function testRelationWithWithOnModel()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with([
        'accounts' => function (HasMany $query) {
            return $query->where('foo', 'bar');
        },
    ]));
}

function testRelationWithArrayWithOnModel()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with([
        'group' => [
            'accounts',
        ],
    ]));
}

function testBelongsToManyCreateReturnsCorrectModel(User $user)
{
    assertType(Post::class, $user->posts()->create());
}

function testNullableUser(ExtendsModelWithPropertyAnnotations $model)
{
    assertType('App\User|null', $model->nullableUser);
}

function testNonNullableUser(ExtendsModelWithPropertyAnnotations $model)
{
    assertType('App\User', $model->nonNullableUser);
}

function testNullableFoo(ExtendsModelWithPropertyAnnotations $model)
{
    assertType('string|null', $model->nullableFoo);
}

function testNonNullableFoo(ExtendsModelWithPropertyAnnotations $model)
{
    assertType('string', $model->nonNullableFoo);
}

/**
 * @property-read User $relation
 */
class RelationCreateExample extends Model
{
    /** @return HasMany<User> */
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
    /** @return HasMany<User> */
    public function relation(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

/**
 * @property-read User|null $nullableUser
 * @property-read User $nonNullableUser
 * @property-read string|null $nullableFoo
 * @property-read string $nonNullableFoo
 */
class ModelWithPropertyAnnotations extends Model
{
    /** @return HasOne<User> */
    public function nullableUser(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasOne<User> */
    public function nonNullableUser(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getNullableFooAttribute(): ?string
    {
        return rand() ? 'foo' : null;
    }

    public function getNonNullableFooAttribute(): string
    {
        return 'foo';
    }
}

class ExtendsModelWithPropertyAnnotations extends ModelWithPropertyAnnotations
{
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

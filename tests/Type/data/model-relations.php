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

function test(User $user, \App\Address $address, Account $account, ExtendsModelWithPropertyAnnotations $model, Tag $tag)
{
    assertType('App\Account', $user->accounts()->firstOrCreate([]));
    assertType(Post::class, $user->posts()->create());
    assertType('App\Account', $user->accounts()->create());
    assertType('App\Account', $user->syncableRelation()->create());
    assertType('int', $user->accounts()->increment('id'));
    assertType('int', $user->accounts()->decrement('id'));
    assertType('int', $user->accounts()->increment('id', 5));
    assertType('int', $user->accounts()->decrement('id', 5));
    assertType('Illuminate\Pagination\LengthAwarePaginator<App\Account>', $user->accounts()->paginate(5));
    assertType(
        'Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, App\Address>',
        $address->addressable()->where('name', 'bar')
    );
    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<App\Address>', $user->address()->where('name', 'bar'));
    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<App\Address>', $user->address()->one());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', $user->accounts()->active());
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->get());
    /** @var Group $group */
    $group = $user->group;

    assertType('App\AccountCollection<int, App\Account>', $group->accounts()->where('active', 1)->get());
    assertType('App\Account', $user->accounts()->make());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', (new User())->accounts()->where('name', 'bar'));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', (new User())->accounts()->whereIn('id', [1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account>', (new User())->accounts()->whereActive(true));
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\Account>', (new User())->accounts()->one());
    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<App\Transaction>', (new User())->transactions());
    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<App\Transaction>', (new User())->transactions()->one());
    assertType('App\Account', getUser()->accounts()->create());
    assertType('App\Account|null', (new User())->accounts()->where('name', 'bar')->first());
    assertType('App\User', User::with('accounts')->whereHas('accounts')->firstOrFail());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, App\Account>', $account->ownerRelation());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Account, App\Account>', $account->parent());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\User>', $user->children());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, App\User>', $user->parent());
    assertType('App\Account|null', $user->accounts()->firstWhere('name', 'bar'));
    assertType('App\Group|null', $user->group()->firstWhere('name', 'bar'));
    assertType('App\Account|null', $user->accounts->first());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $user->group()->withTrashed());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $user->group()->onlyTrashed());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $user->group()->withoutTrashed());
    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelations\Address>', $tag->addresses());
    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelations\Address>', $tag->addresses());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with([
        'accounts' => function (HasMany $query) {
            return $query->where('foo', 'bar');
        },
    ]));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with([
        'group' => [
            'accounts',
        ],
    ]));
    assertType('App\User|null', $model->nullableUser);
    assertType('App\User', $model->nonNullableUser);
    assertType('string|null', $model->nullableFoo);
    assertType('string', $model->nonNullableFoo);

    // Relationship counts
    assertType('int<0, max>', $user->group_count);
    assertType('int<0, max>', $user->accounts_count);
    assertType('int<0, max>', $user->syncableRelation_count);
}

function getUser(): User
{
    return User::firstOrFail();
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

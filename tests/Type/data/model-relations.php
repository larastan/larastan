<?php

namespace ModelRelations;

use App\Account;
use App\Address;
use App\ChildUser;
use App\Comment;
use App\Group;
use App\Post;
use App\Role;
use App\Tag;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function PHPStan\Testing\assertType;

function test(
    User $appUser,
    Address $address,
    Account $account,
    ExtendsModelWithPropertyAnnotations $model,
    Tag $tag,
    User|Account $union,
    User $user,
    Post $post,
    Comment $comment,
    ChildUser $child,
): void {
    assertType('App\Account', $appUser->accounts()->firstOrCreate([]));
    assertType('App\Account', $appUser->accounts()->createOrFirst([]));
    assertType(Post::class, $appUser->posts()->create());
    assertType('App\Account', $appUser->accounts()->create());
    assertType('App\Account', $appUser->syncableRelation()->create());
    assertType('int', $appUser->accounts()->increment('id'));
    assertType('int', $appUser->accounts()->decrement('id'));
    assertType('int', $appUser->accounts()->increment('id', 5));
    assertType('int', $appUser->accounts()->decrement('id', 5));
    assertType('Illuminate\Pagination\LengthAwarePaginator<App\Account>', $appUser->accounts()->paginate(5));
    assertType(
        'Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, App\Address>',
        $address->addressable()->where('name', 'bar')
    );
    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<App\Address, App\User>', $appUser->address()->where('name', 'bar'));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $appUser->accounts()->active());
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->get());
    /** @var Group $group */
    $group = $appUser->group;

    $appUser->__children = $appUser->children;

    assertType('App\AccountCollection<int, App\Account>', $group->accounts()->where('active', 1)->get());
    assertType('App\Account', $appUser->accounts()->make());
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->find([1]));
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->findMany([1, 2, 3]));
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->findOrNew([1]));
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->findOrFail([1]));
    assertType('42|App\RoleCollection<int, App\Role>', $appUser->roles()->findOr([1], fn () => 42));
    assertType('42|App\RoleCollection<int, App\Role>', $appUser->roles()->findOr([1], callback: fn () => 42));
    assertType('App\Role', $appUser->roles()->findOrNew(1));
    assertType('App\Role', $appUser->roles()->findOrFail(1));
    assertType('App\Role|null', $appUser->roles()->find(1));
//    assertType('App\Role|int', $appUser->roles()->findOr(1, fn () => 42));
//    assertType('App\Role|int', $appUser->roles()->findOr(1, callback: fn () => 42));
    assertType('App\Role|null', $appUser->roles()->first());
    assertType('42|App\Role', $appUser->roles()->firstOr(fn () => 42));
    assertType('42|App\Role', $appUser->roles()->firstOr(callback: fn () => 42));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', (new User())->accounts()->where('name', 'bar'));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', (new User())->accounts()->whereIn('id', [1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', (new User())->accounts()->whereActive(true));
    assertType('App\Account', $appUser->accounts()->create());
    assertType('App\Account|null', (new User())->accounts()->where('name', 'bar')->first());
    assertType('App\User', User::with('accounts')->whereHas('accounts')->firstOrFail());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, App\Account>', $account->ownerRelation());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Account, App\Account>', $account->parent());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\User, App\User>', $appUser->children());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $appUser->__children);
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, App\User>', $appUser->parent());
    assertType('App\Account|null', $appUser->accounts()->firstWhere('name', 'bar'));
    assertType('App\Group|null', $appUser->group()->firstWhere('name', 'bar'));
    assertType('App\Account|null', $appUser->accounts->first());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $appUser->group()->withTrashed());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $appUser->group()->onlyTrashed());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $appUser->group()->withoutTrashed());
    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<App\Transaction, App\Account, App\User>', $appUser->transactions());
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
    assertType('int<0, max>', $appUser->group_count);
    assertType('int<0, max>', $appUser->accounts_count);
    assertType('int<0, max>', $appUser->accounts_snake_count);
    assertType('int<0, max>', $appUser->accounts_camel_count);
    assertType('int<0, max>', $appUser->accountsCamel_count);
    assertType('int<0, max>', $appUser->syncable_relation_count);

    $users = (new Post())->users();
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->getEager());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->get());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $users->getQuery());
    assertType('App\User', $users->make());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\Account|App\User>', $union->group());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\Account|App\User>', $union->posts());

    assertType('App\Account', $appUser->accounts()->sole());
    assertType('App\Group', $appUser->group()->sole());
    assertType('App\Post', $appUser->posts()->sole());

    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<App\Address, App\User>', $user->address());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Address>', $user->address()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Address>', $user->address);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Address>', $user->address()->get());
    assertType('App\Address', $user->address()->make());
    assertType('App\Address', $user->address()->create());
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\Address, App\ChildUser>', $child->oneAddress());
    assertType('App\Address', $child->oneAddress()->make());
    assertType('App\Address', $child->oneAddress()->create([]));
    assertType('App\Address', $child->oneAddress()->getRelated());
    assertType('App\ChildUser', $child->oneAddress()->getParent());
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\Address, App\User>', $user->oneAddress());
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\Address, App\User>', $user->oneAddress()->where('zip'));
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\Address, App\User>', $user->oneAddress()->orderBy('zip'));

    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $user->accountsCamel());
    assertType('App\AccountCollection<int, App\Account>', $user->accountsCamel()->getResults());
    assertType('App\AccountCollection<int, App\Account>', $user->accountsCamel);
    assertType('App\AccountCollection<int, App\Account>', $user->accountsCamel()->makeMany([]));
    assertType('App\AccountCollection<int, App\Account>', $user->accountsCamel()->createMany([]));
    assertType('App\AccountCollection<int, App\Account>', $user->accountsCamel()->createManyQuietly([]));
    assertType('App\Account', $user->accountsCamel()->make());
    assertType('App\Account', $user->accountsCamel()->create());
    assertType('App\Account|false', $user->accountsCamel()->save(new Account()));
    assertType('App\Account|false', $user->accountsCamel()->saveQuietly(new Account()));

    assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Role, App\User>', $user->roles());
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->getResults());
    assertType('App\RoleCollection<int, App\Role>', $user->roles);
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->find([1]));
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->findMany([1, 2, 3]));
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->findOrNew([1]));
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->findOrFail([1]));
    assertType('42|App\RoleCollection<int, App\Role>', $user->roles()->findOr([1], fn () => 42));
    assertType('42|App\RoleCollection<int, App\Role>', $user->roles()->findOr([1], callback: fn () => 42));
    assertType('App\Role', $user->roles()->findOrNew(1));
    assertType('App\Role', $user->roles()->findOrFail(1));
    assertType('App\Role|null', $user->roles()->find(1));
//    assertType('int|App\Role', $user->roles()->findOr(1, fn () => 42));
//    assertType('int|App\Role', $user->roles()->findOr(1, callback: fn () => 42));
    assertType('App\Role|null', $user->roles()->first());
    assertType('42|App\Role', $user->roles()->firstOr(fn () => 42));
    assertType('42|App\Role', $user->roles()->firstOr(callback: fn () => 42));
    assertType('App\Role|null', $user->roles()->firstWhere('foo'));
    assertType('App\Role', $user->roles()->firstOrNew());
    assertType('App\Role', $user->roles()->firstOrFail());
    assertType('App\Role', $user->roles()->firstOrCreate());
    assertType('App\Role', $user->roles()->create());
    assertType('App\Role', $user->roles()->createOrFirst());
    assertType('App\Role', $user->roles()->updateOrCreate([]));
    assertType('App\Role', $user->roles()->save(new Role()));
    assertType('App\Role', $user->roles()->saveQuietly(new Role()));
    $roles = $user->roles()->getResults();
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->saveMany($roles));
    assertType('array<int, App\Role>', $user->roles()->saveMany($roles->all()));
    assertType('App\RoleCollection<int, App\Role>', $user->roles()->saveManyQuietly($roles));
    assertType('array<int, App\Role>', $user->roles()->saveManyQuietly($roles->all()));
    assertType('array<int, App\Role>', $user->roles()->createMany($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->sync($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithoutDetaching($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithPivotValues($roles, []));
    assertType('Illuminate\Support\LazyCollection<int, App\Role>', $user->roles()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\Role>', $user->roles()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\Role>', $user->roles()->cursor());

    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<App\Car, App\Mechanic, App\User>', $user->car());
    assertType('App\Car|null', $user->car()->getResults());
    assertType('App\Car|null', $user->car);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Car>', $user->car()->find([1]));
    assertType('42|Illuminate\Database\Eloquent\Collection<int, App\Car>', $user->car()->findOr([1], fn () => 42));
    assertType('42|Illuminate\Database\Eloquent\Collection<int, App\Car>', $user->car()->findOr([1], callback: fn () => 42));
    assertType('App\Car|null', $user->car()->find(1));
//    assertType('int|App\Car', $user->car()->findOr(1, fn () => 42));
//    assertType('int|App\Car', $user->car()->findOr(1, callback: fn () => 42));
    assertType('App\Car|null', $user->car()->first());
    assertType('42|App\Car', $user->car()->firstOr(fn () => 42));
    assertType('42|App\Car', $user->car()->firstOr(callback: fn () => 42));
    assertType('Illuminate\Support\LazyCollection<int, App\Car>', $user->car()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\Car>', $user->car()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\Car>', $user->car()->cursor());
    assertType('Illuminate\Database\Eloquent\PendingHasThroughRelationship<Illuminate\Database\Eloquent\Model, App\User>', $user->through('mechanic'));

    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<App\Part, App\Mechanic, App\User>', $user->parts());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Part>', $user->parts()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Part>', $user->parts);
    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<App\Part, App\Mechanic, App\User>', $user->firstPart());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, App\Post>', $post->user());
    assertType('App\User|null', $post->user()->getResults());
    assertType('App\User|null', $post->user);
    assertType('App\User', $post->user()->make());
    assertType('App\User', $post->user()->create());
    assertType('App\Post', $post->user()->associate(new User()));
    assertType('App\Post', $post->user()->dissociate());
    assertType('App\Post', $post->user()->disassociate());
    assertType('App\Post', $post->user()->getChild());

    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<App\Image, App\Post>', $post->image());
    assertType('App\Image|null', $post->image()->getResults());
    assertType('App\Image|null', $post->image);
    assertType('App\Image', $post->image()->forceCreate([]));

    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<App\Comment, App\Post>', $post->comments());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Comment>', $post->comments()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Comment>', $post->comments);
    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<App\Comment, App\Post>', $post->latestComment());

    assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, App\Comment>', $comment->commentable());
    assertType('Illuminate\Database\Eloquent\Model|null', $comment->commentable()->getResults());
    assertType('Illuminate\Database\Eloquent\Model|null', $comment->commentable);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Comment>', $comment->commentable()->getEager());
    assertType('Illuminate\Database\Eloquent\Model', $comment->commentable()->createModelByType('foo'));
    assertType('App\Comment', $comment->commentable()->associate(new Post()));
    assertType('App\Comment', $comment->commentable()->dissociate());

    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<App\Tag, App\Post>', $post->tags());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Tag>', $post->tags()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Tag>', $post->tags);

    $user->roles()->where(function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Role>', $query);
    });

    assertType(
        'Illuminate\Database\Eloquent\Relations\HasManyThrough<App\Part, App\Mechanic, App\User>',
        $user->through($user->mechanic())->has(fn ($mechanic) => $mechanic->parts()),
    );

    assertType(
        'App\AccountCollection<int, App\Account>',
        $user->accountsCamel()->createMany([
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]),
    );
}

/**
 * @property-read User $relation
 */
class RelationCreateExample extends Model
{
    /** @return HasMany<User, $this> */
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
    /** @return HasMany<User, $this> */
    public function relation(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

/**
 * @property-read User|null   $nullableUser
 * @property-read User        $nonNullableUser
 * @property-read string|null $nullableFoo
 * @property-read string      $nonNullableFoo
 */
class ModelWithPropertyAnnotations extends Model
{
    /** @return HasOne<User, $this> */
    public function nullableUser(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasOne<User, $this> */
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

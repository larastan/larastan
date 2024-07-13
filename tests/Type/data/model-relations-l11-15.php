<?php

namespace ModelRelations;

use App\Account;
use App\Group;
use App\Post as AppPost;
use App\User as AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

use function PHPStan\Testing\assertType;

function test(
    AppUser $appUser,
    \App\Address $address,
    Account $account,
    ExtendsModelWithPropertyAnnotations $model,
    Tag $tag,
    AppUser|Account $union,
    User $user,
    Post $post,
    Comment $comment,
    ChildUser $child,
) {
    assertType('App\Account', $appUser->accounts()->firstOrCreate([]));
    assertType('App\Account', $appUser->accounts()->createOrFirst([]));
    assertType(AppPost::class, $appUser->posts()->create());
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
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', (new AppUser())->accounts()->where('name', 'bar'));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', (new AppUser())->accounts()->whereIn('id', [1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', (new AppUser())->accounts()->whereActive(true));
    assertType('App\Account', $appUser->accounts()->create());
    assertType('App\Account|null', (new AppUser())->accounts()->where('name', 'bar')->first());
    assertType('App\User', AppUser::with('accounts')->whereHas('accounts')->firstOrFail());
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
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', AppUser::with([
        'accounts' => function (HasMany $query) {
            return $query->where('foo', 'bar');
        },
    ]));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', AppUser::with([
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

    $users = (new AppPost())->users();
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->getEager());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->get());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $users->getQuery());
    assertType('App\User', $users->make());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\Account|App\User>', $union->group());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\Account|App\User>', $union->posts());

    assertType('App\Account', $appUser->accounts()->sole());
    assertType('App\Group', $appUser->group()->sole());
    assertType('App\Post', $appUser->posts()->sole());

    assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelations\Address, ModelRelations\User>', $user->address());
    assertType('ModelRelations\Address|null', $user->address()->getResults());
    assertType('ModelRelations\Address|null', $user->address);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Address>', $user->address()->get());
    assertType('ModelRelations\Address', $user->address()->make());
    assertType('ModelRelations\Address', $user->address()->create());
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelations\Address, ModelRelations\ChildUser>', $child->address());
    assertType('ModelRelations\Address', $child->address()->make());
    assertType('ModelRelations\Address', $child->address()->create([]));
    assertType('ModelRelations\Address', $child->address()->getRelated());
    assertType('ModelRelations\ChildUser', $child->address()->getParent());

    assertType('Illuminate\Database\Eloquent\Relations\HasMany<ModelRelations\Post, ModelRelations\User>', $user->posts());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Post>', $user->posts()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Post>', $user->posts);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Post>', $user->posts()->makeMany([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Post>', $user->posts()->createMany([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Post>', $user->posts()->createManyQuietly([]));
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelations\Post, ModelRelations\User>', $user->latestPost());
    assertType('ModelRelations\Post', $user->posts()->make());
    assertType('ModelRelations\Post', $user->posts()->create());
    assertType('ModelRelations\Post|false', $user->posts()->save(new Post()));
    assertType('ModelRelations\Post|false', $user->posts()->saveQuietly(new Post()));

    assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<ModelRelations\Role, ModelRelations\User>', $user->roles());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles()->find([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles()->findMany([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles()->findOrNew([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles()->findOrFail([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>|int', $user->roles()->findOr([1], fn () => 42));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>|int', $user->roles()->findOr([1], callback: fn () => 42));
    assertType('ModelRelations\Role', $user->roles()->findOrNew(1));
    assertType('ModelRelations\Role', $user->roles()->findOrFail(1));
    assertType('ModelRelations\Role|null', $user->roles()->find(1));
    assertType('ModelRelations\Role|int', $user->roles()->findOr(1, fn () => 42));
    assertType('ModelRelations\Role|int', $user->roles()->findOr(1, callback: fn () => 42));
    assertType('ModelRelations\Role|null', $user->roles()->first());
    assertType('ModelRelations\Role|int', $user->roles()->firstOr(fn () => 42));
    assertType('ModelRelations\Role|int', $user->roles()->firstOr(callback: fn () => 42));
    assertType('ModelRelations\Role|null', $user->roles()->firstWhere('foo'));
    assertType('ModelRelations\Role', $user->roles()->firstOrNew());
    assertType('ModelRelations\Role', $user->roles()->firstOrFail());
    assertType('ModelRelations\Role', $user->roles()->firstOrCreate());
    assertType('ModelRelations\Role', $user->roles()->create());
    assertType('ModelRelations\Role', $user->roles()->createOrFirst());
    assertType('ModelRelations\Role', $user->roles()->updateOrCreate([]));
    assertType('ModelRelations\Role', $user->roles()->save(new Role()));
    assertType('ModelRelations\Role', $user->roles()->saveQuietly(new Role()));
    $roles = $user->roles()->getResults();
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles()->saveMany($roles));
    assertType('array<int, ModelRelations\Role>', $user->roles()->saveMany($roles->all()));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Role>', $user->roles()->saveManyQuietly($roles));
    assertType('array<int, ModelRelations\Role>', $user->roles()->saveManyQuietly($roles->all()));
    assertType('array<int, ModelRelations\Role>', $user->roles()->createMany($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->sync($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithoutDetaching($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithPivotValues($roles, []));
    assertType('Illuminate\Support\LazyCollection<int, ModelRelations\Role>', $user->roles()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelations\Role>', $user->roles()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelations\Role>', $user->roles()->cursor());

    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelations\Car, ModelRelations\Mechanic, ModelRelations\User>', $user->car());
    assertType('ModelRelations\Car|null', $user->car()->getResults());
    assertType('ModelRelations\Car|null', $user->car);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Car>', $user->car()->find([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Car>|int', $user->car()->findOr([1], fn () => 42));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Car>|int', $user->car()->findOr([1], callback: fn () => 42));
    assertType('ModelRelations\Car|null', $user->car()->find(1));
    assertType('ModelRelations\Car|int', $user->car()->findOr(1, fn () => 42));
    assertType('ModelRelations\Car|int', $user->car()->findOr(1, callback: fn () => 42));
    assertType('ModelRelations\Car|null', $user->car()->first());
    assertType('ModelRelations\Car|int', $user->car()->firstOr(fn () => 42));
    assertType('ModelRelations\Car|int', $user->car()->firstOr(callback: fn () => 42));
    assertType('Illuminate\Support\LazyCollection<int, ModelRelations\Car>', $user->car()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelations\Car>', $user->car()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelations\Car>', $user->car()->cursor());

    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<ModelRelations\Part, ModelRelations\Mechanic, ModelRelations\User>', $user->parts());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Part>', $user->parts()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Part>', $user->parts);
    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelations\Part, ModelRelations\Mechanic, ModelRelations\User>', $user->firstPart());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<ModelRelations\User, ModelRelations\Post>', $post->user());
    assertType('ModelRelations\User|null', $post->user()->getResults());
    assertType('ModelRelations\User|null', $post->user);
    assertType('ModelRelations\User', $post->user()->make());
    assertType('ModelRelations\User', $post->user()->create());
    assertType('ModelRelations\Post', $post->user()->associate(new User()));
    assertType('ModelRelations\Post', $post->user()->dissociate());
    assertType('ModelRelations\Post', $post->user()->disassociate());
    assertType('ModelRelations\Post', $post->user()->getChild());

    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelations\Image, ModelRelations\Post>', $post->image());
    assertType('ModelRelations\Image|null', $post->image()->getResults());
    assertType('ModelRelations\Image|null', $post->image);
    assertType('ModelRelations\Image', $post->image()->forceCreate([]));

    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<ModelRelations\Comment, ModelRelations\Post>', $post->comments());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Comment>', $post->comments()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Comment>', $post->comments);
    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelations\Comment, ModelRelations\Post>', $post->latestComment());

    assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, ModelRelations\Comment>', $comment->commentable());
    assertType('Illuminate\Database\Eloquent\Model|null', $comment->commentable()->getResults());
    assertType('Illuminate\Database\Eloquent\Model|null', $comment->commentable);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Comment>', $comment->commentable()->getEager());
    assertType('Illuminate\Database\Eloquent\Model', $comment->commentable()->createModelByType('foo'));
    assertType('ModelRelations\Comment', $comment->commentable()->associate(new Post()));
    assertType('ModelRelations\Comment', $comment->commentable()->dissociate());

    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelations\Tag, ModelRelations\Post>', $post->tags());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Tag>', $post->tags()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelations\Tag>', $post->tags);
}

/**
 * @property-read \App\User $relation
 */
class RelationCreateExample extends Model
{
    /** @return HasMany<\App\User, $this> */
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
    /** @return HasMany<\App\User, $this> */
    public function relation(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

/**
 * @property-read \App\User|null $nullableUser
 * @property-read \App\User $nonNullableUser
 * @property-read string|null $nullableFoo
 * @property-read string $nonNullableFoo
 */
class ModelWithPropertyAnnotations extends Model
{
    /** @return HasOne<\App\User, $this> */
    public function nullableUser(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasOne<\App\User, $this> */
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

class User extends Model
{
    /** @return HasOne<Address, $this> */
    public function address(): HasOne
    {
        $hasOne = $this->hasOne(Address::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelations\Address, $this(ModelRelations\User)>', $hasOne);
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelations\Address, $this(ModelRelations\User)>', $hasOne->where('zip'));
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelations\Address, $this(ModelRelations\User)>', $hasOne->orderBy('zip'));

        return $hasOne;
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        $hasMany = $this->hasMany(Post::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<ModelRelations\Post, $this(ModelRelations\User)>', $hasMany);

        return $hasMany;
    }

    /** @return HasOne<Post, $this> */
    public function latestPost(): HasOne
    {
        $post = $this->posts()->one();
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelations\Post, $this(ModelRelations\User)>', $post);

        return $post;
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        $belongsToMany = $this->belongsToMany(Role::class);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<ModelRelations\Role, $this(ModelRelations\User)>', $belongsToMany);

        return $belongsToMany;
    }

    /** @return HasOne<Mechanic, $this> */
    public function mechanic(): HasOne
    {
        return $this->hasOne(Mechanic::class);
    }

    /** @return HasOneThrough<Car, Mechanic, $this> */
    public function car(): HasOneThrough
    {
        $hasOneThrough = $this->hasOneThrough(Car::class, Mechanic::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelations\Car, ModelRelations\Mechanic, $this(ModelRelations\User)>', $hasOneThrough);

        $through = $this->through('mechanic');
        assertType(
            'Illuminate\Database\Eloquent\PendingHasThroughRelationship<Illuminate\Database\Eloquent\Model, $this(ModelRelations\User)>',
            $through,
        );
        assertType(
            'Illuminate\Database\Eloquent\Relations\HasManyThrough<Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Model, $this(ModelRelations\User)>|Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Model, $this(ModelRelations\User)>',
            $through->has('car'),
        );

        $through = $this->through($this->mechanic());
        assertType(
            'Illuminate\Database\Eloquent\PendingHasThroughRelationship<ModelRelations\Mechanic, $this(ModelRelations\User)>',
            $through,
        );
        assertType(
            'Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelations\Car, ModelRelations\Mechanic, $this(ModelRelations\User)>',
            $through->has(function ($mechanic) {
                assertType('ModelRelations\Mechanic', $mechanic);

                return $mechanic->car();
            }),
        );

        return $hasOneThrough;
    }

    /** @return HasManyThrough<Part, Mechanic, $this> */
    public function parts(): HasManyThrough
    {
        $hasManyThrough = $this->hasManyThrough(Part::class, Mechanic::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<ModelRelations\Part, ModelRelations\Mechanic, $this(ModelRelations\User)>', $hasManyThrough);

        assertType(
            'Illuminate\Database\Eloquent\Relations\HasManyThrough<ModelRelations\Part, ModelRelations\Mechanic, $this(ModelRelations\User)>',
            $this->through($this->mechanic())->has(fn ($mechanic) => $mechanic->parts()),
        );

        return $hasManyThrough;
    }

    /** @return HasOneThrough<Part, Mechanic, $this> */
    public function firstPart(): HasOneThrough
    {
        $part = $this->parts()->one();
        assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelations\Part, ModelRelations\Mechanic, $this(ModelRelations\User)>', $part);

        return $part;
    }
}

class Post extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        $belongsTo = $this->belongsTo(User::class);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<ModelRelations\User, $this(ModelRelations\Post)>', $belongsTo);

        return $belongsTo;
    }

    /** @return MorphOne<Image, $this> */
    public function image(): MorphOne
    {
        $morphOne = $this->morphOne(Image::class, 'imageable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelations\Image, $this(ModelRelations\Post)>', $morphOne);

        return $morphOne;
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        $morphMany = $this->morphMany(Comment::class, 'commentable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphMany<ModelRelations\Comment, $this(ModelRelations\Post)>', $morphMany);

        return $morphMany;
    }

    /** @return MorphOne<Comment, $this> */
    public function latestComment(): MorphOne
    {
        $comment = $this->comments()->one();
        assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelations\Comment, $this(ModelRelations\Post)>', $comment);

        return $comment;
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        $morphToMany = $this->morphedByMany(Tag::class, 'taggable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelations\Tag, $this(ModelRelations\Post)>', $morphToMany);

        return $morphToMany;
    }
}

class Comment extends Model
{
    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function commentable(): MorphTo
    {
        $morphTo = $this->morphTo();
        assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, $this(ModelRelations\Comment)>', $morphTo);

        return $morphTo;
    }
}

class Tag extends Model
{
    /** @return MorphToMany<Post, $this> */
    public function posts(): MorphToMany
    {
        $morphToMany = $this->morphToMany(Post::class, 'taggable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelations\Post, $this(ModelRelations\Tag)>', $morphToMany);

        return $morphToMany;
    }
}

class Mechanic extends Model
{
    /** @return HasOne<Car, $this> */
    public function car(): HasOne
    {
        return $this->hasOne(Car::class);
    }

    /** @return HasMany<Part, $this> */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}

class ChildUser extends User
{
}
class Address extends Model
{
}
class Role extends Model
{
}
class Car extends Model
{
}
class Part extends Model
{
}
class Image extends Model
{
}

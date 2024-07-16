<?php

namespace ModelRelationsL11;

use App\Account;
use App\Group;
use App\Post as AppPost;
use App\User as AppUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
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
use Illuminate\Database\Eloquent\Relations\Relation;

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
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->find([1]));
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->findMany([1, 2, 3]));
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->findOrNew([1]));
    assertType('App\RoleCollection<int, App\Role>', $appUser->roles()->findOrFail([1]));
    assertType('App\RoleCollection<int, App\Role>|int', $appUser->roles()->findOr([1], fn () => 42));
    assertType('App\RoleCollection<int, App\Role>|int', $appUser->roles()->findOr([1], callback: fn () => 42));
    assertType('App\Role', $appUser->roles()->findOrNew(1));
    assertType('App\Role', $appUser->roles()->findOrFail(1));
    assertType('App\Role|null', $appUser->roles()->find(1));
    assertType('App\Role|int', $appUser->roles()->findOr(1, fn () => 42));
    assertType('App\Role|int', $appUser->roles()->findOr(1, callback: fn () => 42));
    assertType('App\Role|null', $appUser->roles()->first());
    assertType('App\Role|int', $appUser->roles()->firstOr(fn () => 42));
    assertType('App\Role|int', $appUser->roles()->firstOr(callback: fn () => 42));
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

    assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL11\Address, ModelRelationsL11\User>', $user->address());
    assertType('ModelRelationsL11\Address|null', $user->address()->getResults());
    assertType('ModelRelationsL11\Address|null', $user->address);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Address>', $user->address()->get());
    assertType('ModelRelationsL11\Address', $user->address()->make());
    assertType('ModelRelationsL11\Address', $user->address()->create());
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL11\Address, ModelRelationsL11\ChildUser>', $child->address());
    assertType('ModelRelationsL11\Address', $child->address()->make());
    assertType('ModelRelationsL11\Address', $child->address()->create([]));
    assertType('ModelRelationsL11\Address', $child->address()->getRelated());
    assertType('ModelRelationsL11\ChildUser', $child->address()->getParent());

    assertType('Illuminate\Database\Eloquent\Relations\HasMany<ModelRelationsL11\Post, ModelRelationsL11\User>', $user->posts());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Post>', $user->posts()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Post>', $user->posts);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Post>', $user->posts()->makeMany([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Post>', $user->posts()->createMany([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Post>', $user->posts()->createManyQuietly([]));
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL11\Post, ModelRelationsL11\User>', $user->latestPost());
    assertType('ModelRelationsL11\Post', $user->posts()->make());
    assertType('ModelRelationsL11\Post', $user->posts()->create());
    assertType('ModelRelationsL11\Post|false', $user->posts()->save(new Post()));
    assertType('ModelRelationsL11\Post|false', $user->posts()->saveQuietly(new Post()));

    assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<ModelRelationsL11\Role, ModelRelationsL11\User>', $user->roles());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles()->find([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles()->findMany([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles()->findOrNew([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles()->findOrFail([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>|int', $user->roles()->findOr([1], fn () => 42));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>|int', $user->roles()->findOr([1], callback: fn () => 42));
    assertType('ModelRelationsL11\Role', $user->roles()->findOrNew(1));
    assertType('ModelRelationsL11\Role', $user->roles()->findOrFail(1));
    assertType('ModelRelationsL11\Role|null', $user->roles()->find(1));
    assertType('int|ModelRelationsL11\Role', $user->roles()->findOr(1, fn () => 42));
    assertType('int|ModelRelationsL11\Role', $user->roles()->findOr(1, callback: fn () => 42));
    assertType('ModelRelationsL11\Role|null', $user->roles()->first());
    assertType('int|ModelRelationsL11\Role', $user->roles()->firstOr(fn () => 42));
    assertType('int|ModelRelationsL11\Role', $user->roles()->firstOr(callback: fn () => 42));
    assertType('ModelRelationsL11\Role|null', $user->roles()->firstWhere('foo'));
    assertType('ModelRelationsL11\Role', $user->roles()->firstOrNew());
    assertType('ModelRelationsL11\Role', $user->roles()->firstOrFail());
    assertType('ModelRelationsL11\Role', $user->roles()->firstOrCreate());
    assertType('ModelRelationsL11\Role', $user->roles()->create());
    assertType('ModelRelationsL11\Role', $user->roles()->createOrFirst());
    assertType('ModelRelationsL11\Role', $user->roles()->updateOrCreate([]));
    assertType('ModelRelationsL11\Role', $user->roles()->save(new Role()));
    assertType('ModelRelationsL11\Role', $user->roles()->saveQuietly(new Role()));
    $roles = $user->roles()->getResults();
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles()->saveMany($roles));
    assertType('array<int, ModelRelationsL11\Role>', $user->roles()->saveMany($roles->all()));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Role>', $user->roles()->saveManyQuietly($roles));
    assertType('array<int, ModelRelationsL11\Role>', $user->roles()->saveManyQuietly($roles->all()));
    assertType('array<int, ModelRelationsL11\Role>', $user->roles()->createMany($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->sync($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithoutDetaching($roles));
    assertType('array{attached: array, detached: array, updated: array}', $user->roles()->syncWithPivotValues($roles, []));
    assertType('Illuminate\Support\LazyCollection<int, ModelRelationsL11\Role>', $user->roles()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelationsL11\Role>', $user->roles()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelationsL11\Role>', $user->roles()->cursor());

    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelationsL11\Car, ModelRelationsL11\Mechanic, ModelRelationsL11\User>', $user->car());
    assertType('ModelRelationsL11\Car|null', $user->car()->getResults());
    assertType('ModelRelationsL11\Car|null', $user->car);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Car>', $user->car()->find([1]));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Car>|int', $user->car()->findOr([1], fn () => 42));
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Car>|int', $user->car()->findOr([1], callback: fn () => 42));
    assertType('ModelRelationsL11\Car|null', $user->car()->find(1));
    assertType('int|ModelRelationsL11\Car', $user->car()->findOr(1, fn () => 42));
    assertType('int|ModelRelationsL11\Car', $user->car()->findOr(1, callback: fn () => 42));
    assertType('ModelRelationsL11\Car|null', $user->car()->first());
    assertType('int|ModelRelationsL11\Car', $user->car()->firstOr(fn () => 42));
    assertType('int|ModelRelationsL11\Car', $user->car()->firstOr(callback: fn () => 42));
    assertType('Illuminate\Support\LazyCollection<int, ModelRelationsL11\Car>', $user->car()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelationsL11\Car>', $user->car()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, ModelRelationsL11\Car>', $user->car()->cursor());

    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<ModelRelationsL11\Part, ModelRelationsL11\Mechanic, ModelRelationsL11\User>', $user->parts());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Part>', $user->parts()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Part>', $user->parts);
    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelationsL11\Part, ModelRelationsL11\Mechanic, ModelRelationsL11\User>', $user->firstPart());

    assertType('ModelRelationsL11\HasDateOfLatestPost', $user->dateOfLatestPost());
    assertType('Carbon\CarbonImmutable', $user->dateOfLatestPost()->getResults());
    assertType('Carbon\CarbonImmutable', $user->dateOfLatestPost);

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<ModelRelationsL11\User, ModelRelationsL11\Post>', $post->user());
    assertType('ModelRelationsL11\User|null', $post->user()->getResults());
    assertType('ModelRelationsL11\User|null', $post->user);
    assertType('ModelRelationsL11\User', $post->user()->make());
    assertType('ModelRelationsL11\User', $post->user()->create());
    assertType('ModelRelationsL11\Post', $post->user()->associate(new User()));
    assertType('ModelRelationsL11\Post', $post->user()->dissociate());
    assertType('ModelRelationsL11\Post', $post->user()->disassociate());
    assertType('ModelRelationsL11\Post', $post->user()->getChild());

    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelationsL11\Image, ModelRelationsL11\Post>', $post->image());
    assertType('ModelRelationsL11\Image|null', $post->image()->getResults());
    assertType('ModelRelationsL11\Image|null', $post->image);
    assertType('ModelRelationsL11\Image', $post->image()->forceCreate([]));

    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<ModelRelationsL11\Comment, ModelRelationsL11\Post>', $post->comments());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Comment>', $post->comments()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Comment>', $post->comments);
    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelationsL11\Comment, ModelRelationsL11\Post>', $post->latestComment());

    assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, ModelRelationsL11\Comment>', $comment->commentable());
    assertType('Illuminate\Database\Eloquent\Model|null', $comment->commentable()->getResults());
    assertType('Illuminate\Database\Eloquent\Model|null', $comment->commentable);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Comment>', $comment->commentable()->getEager());
    assertType('Illuminate\Database\Eloquent\Model', $comment->commentable()->createModelByType('foo'));
    assertType('ModelRelationsL11\Comment', $comment->commentable()->associate(new Post()));
    assertType('ModelRelationsL11\Comment', $comment->commentable()->dissociate());

    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelationsL11\Tag, ModelRelationsL11\Post>', $post->tags());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Tag>', $post->tags()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelRelationsL11\Tag>', $post->tags);
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
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL11\Address, $this(ModelRelationsL11\User)>', $hasOne);
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL11\Address, $this(ModelRelationsL11\User)>', $hasOne->where('zip'));
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL11\Address, $this(ModelRelationsL11\User)>', $hasOne->orderBy('zip'));

        return $hasOne;
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        $hasMany = $this->hasMany(Post::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<ModelRelationsL11\Post, $this(ModelRelationsL11\User)>', $hasMany);

        return $hasMany;
    }

    /** @return HasOne<Post, $this> */
    public function latestPost(): HasOne
    {
        $post = $this->posts()->one();
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL11\Post, $this(ModelRelationsL11\User)>', $post);

        return $post;
    }

    public function dateOfLatestPost(): HasDateOfLatestPost
    {
        return new HasDateOfLatestPost(Post::query(), $this);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        $belongsToMany = $this->belongsToMany(Role::class);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<ModelRelationsL11\Role, $this(ModelRelationsL11\User)>', $belongsToMany);

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
        assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelationsL11\Car, ModelRelationsL11\Mechanic, $this(ModelRelationsL11\User)>', $hasOneThrough);

        $through = $this->through('mechanic');
        assertType(
            'Illuminate\Database\Eloquent\PendingHasThroughRelationship<Illuminate\Database\Eloquent\Model, $this(ModelRelationsL11\User)>',
            $through,
        );
        assertType(
            'Illuminate\Database\Eloquent\Relations\HasManyThrough<Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Model, $this(ModelRelationsL11\User)>|Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Model, $this(ModelRelationsL11\User)>',
            $through->has('car'),
        );

        $through = $this->through($this->mechanic());
        assertType(
            'Illuminate\Database\Eloquent\PendingHasThroughRelationship<ModelRelationsL11\Mechanic, $this(ModelRelationsL11\User)>',
            $through,
        );
        assertType(
            'Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelationsL11\Car, ModelRelationsL11\Mechanic, $this(ModelRelationsL11\User)>',
            $through->has(function ($mechanic) {
                assertType('ModelRelationsL11\Mechanic', $mechanic);

                return $mechanic->car();
            }),
        );

        return $hasOneThrough;
    }

    /** @return HasManyThrough<Part, Mechanic, $this> */
    public function parts(): HasManyThrough
    {
        $hasManyThrough = $this->hasManyThrough(Part::class, Mechanic::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<ModelRelationsL11\Part, ModelRelationsL11\Mechanic, $this(ModelRelationsL11\User)>', $hasManyThrough);

        assertType(
            'Illuminate\Database\Eloquent\Relations\HasManyThrough<ModelRelationsL11\Part, ModelRelationsL11\Mechanic, $this(ModelRelationsL11\User)>',
            $this->through($this->mechanic())->has(fn ($mechanic) => $mechanic->parts()),
        );

        return $hasManyThrough;
    }

    /** @return HasOneThrough<Part, Mechanic, $this> */
    public function firstPart(): HasOneThrough
    {
        $part = $this->parts()->one();
        assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelationsL11\Part, ModelRelationsL11\Mechanic, $this(ModelRelationsL11\User)>', $part);

        return $part;
    }
}

class Post extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        $belongsTo = $this->belongsTo(User::class);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<ModelRelationsL11\User, $this(ModelRelationsL11\Post)>', $belongsTo);

        return $belongsTo;
    }

    /** @return MorphOne<Image, $this> */
    public function image(): MorphOne
    {
        $morphOne = $this->morphOne(Image::class, 'imageable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelationsL11\Image, $this(ModelRelationsL11\Post)>', $morphOne);

        return $morphOne;
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        $morphMany = $this->morphMany(Comment::class, 'commentable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphMany<ModelRelationsL11\Comment, $this(ModelRelationsL11\Post)>', $morphMany);

        return $morphMany;
    }

    /** @return MorphOne<Comment, $this> */
    public function latestComment(): MorphOne
    {
        $comment = $this->comments()->one();
        assertType('Illuminate\Database\Eloquent\Relations\MorphOne<ModelRelationsL11\Comment, $this(ModelRelationsL11\Post)>', $comment);

        return $comment;
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        $morphToMany = $this->morphedByMany(Tag::class, 'taggable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelationsL11\Tag, $this(ModelRelationsL11\Post)>', $morphToMany);

        return $morphToMany;
    }
}

class Comment extends Model
{
    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function commentable(): MorphTo
    {
        $morphTo = $this->morphTo();
        assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, $this(ModelRelationsL11\Comment)>', $morphTo);

        return $morphTo;
    }
}

class Tag extends Model
{
    /** @return MorphToMany<Post, $this> */
    public function posts(): MorphToMany
    {
        $morphToMany = $this->morphToMany(Post::class, 'taggable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<ModelRelationsL11\Post, $this(ModelRelationsL11\Tag)>', $morphToMany);

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

/** @extends Relation<Post, User, \Carbon\CarbonImmutable> */
class HasDateOfLatestPost extends Relation
{

    /** @inheritDoc */
    public function addConstraints(): void
    {
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models): void
    {
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation): array
    {
        return $models;
    }

    /** @inheritDoc */
    public function match(array $models, Collection $results, $relation): array
    {
        return $models;
    }

    /** @inheritDoc */
    public function getResults(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}

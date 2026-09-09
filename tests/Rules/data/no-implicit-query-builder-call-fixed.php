<?php

namespace NoImplicitQueryBuilderCall;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/** @property int $id */
class User extends Model
{
    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /** @param Builder<static> $query */
    protected function scopeRecent(Builder $query): void
    {
        $query->latest();
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function inside(): void
    {
        self::query()->where('id', 1);
        static::query()->active();
        $this->newQuery()->recent();
        $this->scopeActive($this->newQuery());
        $this->scopeRecent($this->newQuery());
    }
}

/** @extends Builder<Post> */
class PostBuilder extends Builder
{
    public function published(): static
    {
        return $this->where('published', true);
    }
}

class Post extends Model
{
    public function newEloquentBuilder($query): PostBuilder
    {
        return new PostBuilder($query);
    }
}

class CollidingModel extends User
{
    public static function find(int $id): ?self
    {
        return null;
    }

    public function orderBy(string $column): bool
    {
        return true;
    }

    public function active(): bool
    {
        return true;
    }

    protected function latest(): bool
    {
        return true;
    }
}

class ChildModel extends CollidingModel
{
}

function forwarded(User $user, Post $post): void
{
    User::query()->where('id', 1);
    User::query()->find(1);
    User::query()->create([]);
    User::query()->first();
    User::query()->whereIn('id', [1, 2]);
    User::query()->active();
    User::query()->recent();
    User::query()->whereId(1);
    Post::query()->published();
    $user->newQuery()->where('id', 1);
    $user->newQuery()->find(1);
    $user->newQuery()->active();
    $user->newQuery()->recent();
    $user->newQuery()->whereId(1);
    $post->newQuery()->published();
}

function direct(User $user, ChildModel $child): void
{
    User::query()->where('id', 1)->first();
    $user->newQuery()->active();
    User::all();
    User::with('posts');
    User::destroy(1);
    $user->save();
    $user->delete();
    $user->increment('id');
    $user->posts()->where('id', 1);
    $user->scopeActive($user->newQuery());
    CollidingModel::find(1);
    ChildModel::find(1);
    $child->orderBy('id');
    $child->active();
    $child->latest();
    Collection::make([]);
    $user->undefinedMethod();
    User::undefinedMethod();
}

/** @param class-string<User> $class */
function classNames(string $class, User $user): void
{
    $class::query()->where('id', 1);
    $user::query()->active();
    $known = User::class;
    $known::query()->find(1);
}

/** @param User|Builder<User> $receiver */
function ambiguous(User|Builder $receiver, User|Post $models, mixed $mixed, object $object, string $method): void
{
    $receiver->where('id', 1);
    $models->where('id', 1);
    $mixed->where('id', 1);
    $object->where('id', 1);
    User::$method();
}

function nullable(?User $user): void
{
    $user?->newQuery()->where('id', 1);
}

function undefinedDynamicWhere(User $user): void
{
    User::whereNonexistentColumn(1);
    $user->whereNonexistentColumn(1);
}

/** @param list<mixed> $arguments */
function callSyntax(User $user, array $arguments): void
{
    User::query()->where(column: 'id', operator: '=', value: 1)->first();
    User::query()->where(...$arguments);
    User::where(...);
    $user->where(...);
    $user->fresh()?->newQuery()->where('id', 1)->first();
    (new User)->newQuery()->where('id', 1);
    // Keep the explanation above the query.
    User::query()->where(
        'id',
        // Keep the column comment.
        1
    )->first();
}

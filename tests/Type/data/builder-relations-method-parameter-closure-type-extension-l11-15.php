<?php

namespace BuilderRelationsMethodParameterClosureTypeExtensionL1115;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use function PHPStan\Testing\assertType;

function test(): void
{
    User::query()->has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    User::query()->has('teams', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Team>', $query);
    });

    User::query()->whereHas('accounts.group', function (Builder $qu) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Group>', $qu);
    });

    User::query()->has('accounts.group', callback: function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Group>', $query);
    });

    Post::query()->has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    Post::query()->has('users.teams', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Team>', $query);
    });

    User::query()->doesntHave('accounts', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->doesntHave('users', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->whereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->whereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->orWhereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->orWhereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->hasMorph('users', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->doesntHaveMorph('users', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->whereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->whereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->orWhereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->orWhereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->whereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->whereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->orWhereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->whereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->whereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::query()->orWhereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::query()->orWhereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    Comment::query()->whereDoesntHaveMorph('commentable', Post::class, function (Builder $query, string $type) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Post>', $query);
        assertType("'BuilderRelationsMethodParameterClosureTypeExtensionL1115\\\Post'", $type);
    });

    User::whereRelation('posts', function(Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Post>', $query);
    });

    Comment::orWhereDoesntHaveMorph('commentable', [Post::class, User::class], function (Builder $query, string $type) {
        assertType('Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\Post>|Illuminate\Database\Eloquent\Builder<BuilderRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
        assertType("'BuilderRelationsMethodParameterClosureTypeExtensionL1115\\\Post'|'BuilderRelationsMethodParameterClosureTypeExtensionL1115\\\User'", $type);
    });

    Comment::orWhereDoesntHaveMorph('commentable', ['*'], function (Builder $query, string $type) {
        assertType('Illuminate\Database\Eloquent\Builder<*>', $query);
        assertType('string', $type);
    });
}

class User extends Model
{
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}

class Post extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

class Account extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}

class Group extends Model
{
}

class Team extends Model
{
}

class Comment extends Model
{
    /** @return MorphTo<Model, $this> */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}


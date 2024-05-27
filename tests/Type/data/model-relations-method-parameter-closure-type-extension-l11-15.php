<?php

namespace ModelRelationsMethodParameterClosureTypeExtensionL1115;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use function PHPStan\Testing\assertType;

function test(): void
{
    User::has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    User::has('teams', '=', 1, 'and', function (Builder $query) {
        assertType('ModelRelationsMethodParameterClosureTypeExtensionL1115\TeamBuilder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Team>', $query);
    });

    User::whereHas('accounts.group', function (Builder $qu) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Group>', $qu);
    });

    User::has('accounts.group', callback: function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Group>', $query);
    });

    Post::has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    Post::has('users.teams', '=', 1, 'and', function (Builder $query) {
        assertType('ModelRelationsMethodParameterClosureTypeExtensionL1115\TeamBuilder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Team>', $query);
    });

    User::doesntHave('accounts', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::doesntHave('users', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::whereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::whereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::orWhereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::orWhereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::hasMorph('users', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::doesntHaveMorph('users', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::whereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::whereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::orWhereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::orWhereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::whereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::whereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::orWhereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::whereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::whereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    User::orWhereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Post::orWhereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
    });

    Comment::whereDoesntHaveMorph('commentable', Post::class, function (Builder $query, string $type) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Post>', $query);
        assertType("'ModelRelationsMethodParameterClosureTypeExtensionL1115\\\Post'", $type);
    });

    User::whereRelation('accounts', function(Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Account>', $query);
    });

    Comment::orWhereDoesntHaveMorph('commentable', [Post::class, User::class], function (Builder $query, string $type) {
        assertType('Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\Post>|Illuminate\Database\Eloquent\Builder<ModelRelationsMethodParameterClosureTypeExtensionL1115\User>', $query);
        assertType("'ModelRelationsMethodParameterClosureTypeExtensionL1115\\\Post'|'ModelRelationsMethodParameterClosureTypeExtensionL1115\\\User'", $type);
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
    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return TeamBuilder<Team>
     */
    public function newEloquentBuilder($query): TeamBuilder
    {
        return new TeamBuilder($query);
    }
}

/**
 * @template TModelClass of Model
 * @extends Builder<TModelClass>
 */
class TeamBuilder extends Builder
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

<?php

namespace BuilderRelationsMethodParameterClosureTypeExtensionL1115;

use Illuminate\Database\Eloquent\Builder;
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
}

class User extends \Illuminate\Database\Eloquent\Model
{
    public function accounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function teams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Team::class);
    }
}

class Post extends \Illuminate\Database\Eloquent\Model
{
    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }
}

class Account extends \Illuminate\Database\Eloquent\Model
{
    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}

class Group extends \Illuminate\Database\Eloquent\Model
{
}

class Team extends \Illuminate\Database\Eloquent\Model
{
}


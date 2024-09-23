<?php

namespace ModelPropertyBuilder;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @param  Builder<User> $builder
 * @param 'unionNotExisting'|'id' $union
 */
function test(Builder $builder, User $user, string $union): void
{
    User::query()->firstWhere('foo', 'bar');
    User::query()->firstWhere($union, 'bar');
    User::query()->where('foo', 'bar')->get();

    $builder->where('foo', 'bar');
    $user->where('foo', 'bar');

    User::query()->firstWhere(DB::raw('name LIKE \'%john%\''));

    User::query()->where(getKey(), '=', 'foo');
    User::query()->orWhere('foo', '=', 'foo');
    User::query()->orWhere('foo', 'foo');
    User::query()->orWhere([
        'foo' => 'foo',
    ]);
    User::query()->value('foo');

    User::query()->where('propertyDefinedOnlyInAnnotation', 'foo');
    User::query()->where('only_available_with_accessor', 'foo');

    User::query()->first(['foo', 'bar']);
    User::query()->first('foo');

    // Joins
    User::query()->join('roles', 'users.role_id', '=', 'roles.id')->where('roles.foo', 'admin');
}

User::query()->whereHas('accounts', function (Builder $query) {
   $query->where('foo', 'bar');
});

function getKey(): string
{
    if (random_int(0, 1)) {
        return 'foo';
    }

    return 'bar';
}

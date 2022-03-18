<?php

\App\User::query()->firstWhere('foo', 'bar');
\App\User::query()->where('foo', 'bar')->get();

/** @return \Illuminate\Database\Eloquent\Builder<\App\User> */
function foo(\App\User $user): \Illuminate\Database\Eloquent\Builder
{
    return $user->where('foo', 'bar');
}

/**
 * @param  \Illuminate\Database\Eloquent\Builder<\App\User>  $builder
 * @return \Illuminate\Database\Eloquent\Builder<\App\User>
 */
function bar(\Illuminate\Database\Eloquent\Builder $builder): \Illuminate\Database\Eloquent\Builder
{
    return $builder->where('foo', 'bar');
}

\App\User::query()->firstWhere(\Illuminate\Support\Facades\DB::raw('name LIKE \'%john%\''));

\App\User::query()->whereColumn('foo', '=', 'foo');
\App\User::query()->where(getKey(), '=', 'foo');
\App\User::query()->orWhere('foo', '=', 'foo');
\App\User::query()->orWhere('foo', 'foo');
\App\User::query()->orWhere([
    'foo' => 'foo',
]);
\App\User::query()->value('foo');

\App\User::query()->where('propertyDefinedOnlyInAnnotation', 'foo');
\App\User::query()->where('only_available_with_accessor', 'foo');

// Currently, there is no way to change the type of `$query` inside the callback.
// So until we have a way to do that, we will ignore errors for `Builder<Model>`
// @see https://github.com/phpstan/phpstan/discussions/6850
\App\User::query()->whereHas('accounts', function (\Illuminate\Database\Eloquent\Builder $query) {
    $query->where('foo', 'bar');
});

function getKey(): string
{
    if (random_int(0, 1)) {
        return 'foo';
    }

    return 'bar';
}

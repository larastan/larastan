<?php

/** @var \App\User $user */
$user->accounts()->where('foo', 'bar');
$user->accounts()->create(['foo' => 'bar']);
$user->accounts()->firstOrNew(['foo' => 'bar']);
$user->accounts()->firstOrCreate(['foo' => 'bar']);
$user->accounts()->createOrFirst(['foo' => 'bar']);
$user->accounts()->updateOrCreate(['foo' => 'bar']);

$user->posts()->where('foo', 'bar');


// Testing closure parameter type extensions
\App\User::query()->whereHas('accounts.group', fn(\Illuminate\Database\Eloquent\Builder $qu) => $qu->where('id', 5));
\App\User::query()->whereHas('accounts.group', function (\Illuminate\Database\Eloquent\Builder $qu) {
    return $qu->whereIn('id', [1, 2, 3]);
});

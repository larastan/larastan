<?php

declare(strict_types=1);

\App\User::query()->has('foo');
\App\User::query()->orHas('foo');
\App\User::query()->doesntHave('foo');
\App\User::query()->orDoesntHave('foo');
\App\User::query()->whereHas('foo');
\App\User::query()->withWhereHas('foo');
\App\User::query()->orWhereHas('foo');
\App\User::query()->whereDoesntHave('foo');
\App\User::query()->orWhereDoesntHave('foo');

\App\User::first()->group()->has('foo');
\App\User::first()->group()->orHas('foo');
\App\User::first()->group()->doesntHave('foo');
\App\User::first()->group()->orDoesntHave('foo');
\App\User::first()->group()->whereHas('foo');
\App\User::first()->group()->withWhereHas('foo');
\App\User::first()->group()->orWhereHas('foo');
\App\User::first()->group()->whereDoesntHave('foo');
\App\User::first()->group()->orWhereDoesntHave('foo');

\App\User::query()->has('accounts.foo');
\App\User::query()->orHas('accounts.foo');
\App\User::query()->doesntHave('accounts.foo');
\App\User::query()->orDoesntHave('accounts.foo');
\App\User::query()->whereHas('accounts.foo');
\App\User::query()->withWhereHas('accounts.foo');
\App\User::query()->orWhereHas('accounts.foo');
\App\User::query()->whereDoesntHave('accounts.foo');
\App\User::query()->orWhereDoesntHave('accounts.foo');

\App\Post::query()->has('users.transactions.foo');
\App\Post::query()->orHas('users.transactions.foo');
\App\Post::query()->doesntHave('users.transactions.foo');
\App\Post::query()->orDoesntHave('users.transactions.foo');
\App\Post::query()->whereHas('users.transactions.foo');
\App\Post::query()->withWhereHas('users.transactions.foo');
\App\Post::query()->orWhereHas('users.transactions.foo');
\App\Post::query()->whereDoesntHave('users.transactions.foo');
\App\Post::query()->orWhereDoesntHave('users.transactions.foo');

\App\User::with('foo');
\App\User::query()->with('foo');
\App\User::with(['foo', 'accounts']);
\App\User::query()->with(['foo', 'accounts']);
\App\User::query()->with(['foo', 'accounts.foo']);
\App\User::query()->with(['foo' => static function () {
}, 'accounts']);

\App\User::with('foo:id,name');
\App\User::query()->with('foo:id,name');
\App\User::with(['foo:id', 'accounts']);
\App\User::query()->with(['foo:id', 'accounts']);

/** @var array{foo:string}|array{0: 'bar'} $a */
\App\User::with($a);

\App\User::query()->whereRelation('foo', 'id');
\App\User::first()->group()->whereRelation('foo', 'id');
\App\User::query()->whereRelation('accounts.foo', 'id');
\App\Post::query()->whereRelation('users.transactions.foo', 'id');

\App\User::query()->has('unionTypeRelation');
\App\User::query()->orHas('unionTypeRelation');
\App\User::query()->doesntHave('unionTypeRelation');
\App\User::query()->orDoesntHave('unionTypeRelation');
\App\User::query()->whereHas('unionTypeRelation');
\App\User::query()->withWhereHas('unionTypeRelation');
\App\User::query()->orWhereHas('unionTypeRelation');
\App\User::query()->whereDoesntHave('unionTypeRelation');
\App\User::query()->orWhereDoesntHave('unionTypeRelation');

\App\User::first()->group()->has('unionTypeRelation');
\App\User::first()->group()->orHas('unionTypeRelation');
\App\User::first()->group()->doesntHave('unionTypeRelation');
\App\User::first()->group()->orDoesntHave('unionTypeRelation');
\App\User::first()->group()->whereHas('unionTypeRelation');
\App\User::first()->group()->withWhereHas('unionTypeRelation');
\App\User::first()->group()->orWhereHas('unionTypeRelation');
\App\User::first()->group()->whereDoesntHave('unionTypeRelation');
\App\User::first()->group()->orWhereDoesntHave('unionTypeRelation');

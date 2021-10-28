<?php

declare(strict_types=1);

\App\User::query()->has('foo');
\App\User::query()->orHas('foo');
\App\User::query()->doesntHave('foo');
\App\User::query()->orDoesntHave('foo');
\App\User::query()->whereHas('foo');
\App\User::query()->orWhereHas('foo');
\App\User::query()->whereDoesntHave('foo');
\App\User::query()->orWhereDoesntHave('foo');

\App\User::group()->has('foo');
\App\User::group()->orHas('foo');
\App\User::group()->doesntHave('foo');
\App\User::group()->orDoesntHave('foo');
\App\User::group()->whereHas('foo');
\App\User::group()->orWhereHas('foo');
\App\User::group()->whereDoesntHave('foo');
\App\User::group()->orWhereDoesntHave('foo');

\App\User::query()->has('accounts.foo');
\App\User::query()->orHas('accounts.foo');
\App\User::query()->doesntHave('accounts.foo');
\App\User::query()->orDoesntHave('accounts.foo');
\App\User::query()->whereHas('accounts.foo');
\App\User::query()->orWhereHas('accounts.foo');
\App\User::query()->whereDoesntHave('accounts.foo');
\App\User::query()->orWhereDoesntHave('accounts.foo');

\App\Post::query()->has('users.transactions.foo');
\App\Post::query()->orHas('users.transactions.foo');
\App\Post::query()->doesntHave('users.transactions.foo');
\App\Post::query()->orDoesntHave('users.transactions.foo');
\App\Post::query()->whereHas('users.transactions.foo');
\App\Post::query()->orWhereHas('users.transactions.foo');
\App\Post::query()->whereDoesntHave('users.transactions.foo');
\App\Post::query()->orWhereDoesntHave('users.transactions.foo');

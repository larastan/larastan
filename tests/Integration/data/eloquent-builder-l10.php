<?php

namespace EloquentBuilderLaravel10;

use App\Post;
use App\User;
use App\Team;
use Illuminate\Support\Facades\DB;

User::query()->where(DB::raw('1'), 1)->get();

/** @see https://github.com/larastan/larastan/issues/1806 */
User::query()->orderBy(Post::query()->select('id')->whereColumn('user_id', 'users.id'));
User::query()->orderByDesc(Post::query()->select('id')->whereColumn('user_id', 'users.id'));

User::query()->get()->pluck('computed');

/** @see https://github.com/larastan/larastan/issues/1952 */
Team::query()->where('name', 'Team A')->orderBy('name')->get();

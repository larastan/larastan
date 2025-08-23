<?php

namespace ModelBuilder;

use App\Post;
use App\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class User extends Model
{
    /** @return Builder<static> */
    public static function testQueryStatic(): Builder
    {
        return static::query();
    }

    public static function testCreateStatic(): static
    {
        return static::query()->create();
    }

    public static function testCreateSelf(): self
    {
        return self::query()->create();
    }
}

function test(): void
{
    \App\User::query()->where(DB::raw('1'), 1)->get();

    /** @see https://github.com/larastan/larastan/issues/1806 */
    \App\User::query()->orderBy(Post::query()->select('id')->whereColumn('user_id', 'users.id'));
    \App\User::query()->orderByDesc(Post::query()->select('id')->whereColumn('user_id', 'users.id'));

    \App\User::query()->get()->pluck('computed');

    /** @see https://github.com/larastan/larastan/issues/1952 */
    Team::query()->where('name', 'Team A')->orderBy('name')->get();
}

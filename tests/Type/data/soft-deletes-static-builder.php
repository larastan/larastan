<?php

declare(strict_types=1);

namespace SoftDeletesStaticBuilder;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function PHPStan\Testing\assertType;

class Post extends Model
{
    use SoftDeletes;

    /** @param Builder<static> $query */
    public function staticBuilder(Builder $query): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<static(SoftDeletesStaticBuilder\Post)>', $query->onlyTrashed());
        assertType('Illuminate\Database\Eloquent\Builder<static(SoftDeletesStaticBuilder\Post)>', $query->withTrashed());
        assertType('Illuminate\Database\Eloquent\Builder<static(SoftDeletesStaticBuilder\Post)>', $query->withoutTrashed());
        assertType('Illuminate\Database\Eloquent\Builder<static(SoftDeletesStaticBuilder\Post)>', $query->onlyTrashed()->where('id', 1));
        assertType('static(SoftDeletesStaticBuilder\Post)', $query->restoreOrCreate(['id' => 1]));
        assertType('int', $query->restore());
    }

    /** @param Builder<$this> $query */
    public function thisBuilder(Builder $query): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<$this(SoftDeletesStaticBuilder\Post)>', $query->onlyTrashed());
    }
}

/** @param Builder<User> $query */
function concreteBuilder(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query->onlyTrashed());
}

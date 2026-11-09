<?php

declare(strict_types=1);

namespace SoftDeletesStaticBuilder;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function PHPStan\Testing\assertType;

/**
 * @template TModelClass of Model
 * @extends Builder<TModelClass>
 */
class RenamedTemplateBuilder extends Builder
{
}

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
        assertType('static(SoftDeletesStaticBuilder\Post)', $query->createOrRestore(['id' => 1]));
        assertType('int', $query->restore());
    }

    /** @param Builder<$this> $query */
    public function thisBuilder(Builder $query): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<$this(SoftDeletesStaticBuilder\Post)>', $query->onlyTrashed());
        assertType('$this(SoftDeletesStaticBuilder\Post)', $query->restoreOrCreate(['id' => 1]));
    }

    /** @param RenamedTemplateBuilder<static> $query */
    public function renamedTemplateBuilder(RenamedTemplateBuilder $query): void
    {
        assertType('SoftDeletesStaticBuilder\RenamedTemplateBuilder<static(SoftDeletesStaticBuilder\Post)>', $query->onlyTrashed());
        assertType('static(SoftDeletesStaticBuilder\Post)', $query->restoreOrCreate(['id' => 1]));
        assertType('static(SoftDeletesStaticBuilder\Post)', $query->createOrRestore(['id' => 1]));
    }
}

/**
 * @template TPost of Post
 * @param Builder<TPost> $query
 */
function templateBuilder(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<TPost of SoftDeletesStaticBuilder\Post (function SoftDeletesStaticBuilder\templateBuilder(), argument)>', $query->onlyTrashed());
    assertType('TPost of SoftDeletesStaticBuilder\Post (function SoftDeletesStaticBuilder\templateBuilder(), argument)', $query->restoreOrCreate(['id' => 1]));
}

/** @param Builder<User> $query */
function concreteBuilder(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query->onlyTrashed());
}

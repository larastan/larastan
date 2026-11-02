<?php

declare(strict_types=1);

namespace CustomBuilderReturnThis;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @property string $status */
class Post extends Model
{
    /** @return PostBuilder<self> */
    public function newEloquentBuilder($query): PostBuilder
    {
        return new PostBuilder($query);
    }
}

/**
 * @template TModel of Post
 *
 * @extends Builder<TModel>
 */
class PostBuilder extends Builder
{
    /** @return $this */
    public function wherePublished(): static
    {
        return $this->where('status', 'published');
    }

    /** @return $this */
    public function withActiveScope(): static
    {
        return $this->withGlobalScope('active', static fn (Builder $query) => $query);
    }

    /** @return $this */
    public function withoutActiveScope(): static
    {
        return $this->withoutGlobalScope('active');
    }
}

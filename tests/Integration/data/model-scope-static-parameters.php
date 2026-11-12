<?php

namespace ModelScopeStaticParameters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @template TModel of Model */
class Filter
{
}

/** @extends Filter<Post> */
class PostFilter extends Filter
{
}

/** @extends Filter<Comment> */
class CommentFilter extends Filter
{
}

class Post extends Model
{
    /**
     * @param Builder<static> $query
     * @param Filter<static>  $filter
     */
    public function scopeFiltered(Builder $query, Filter $filter): void
    {
    }

    /**
     * @param Builder<static> $query
     * @param static          $post
     */
    public function scopeLike(Builder $query, self $post): void
    {
    }

    /**
     * @param Builder<static> $query
     * @param $this           $post
     */
    public function scopeSibling(Builder $query, self $post): void
    {
    }

    /**
     * @param Builder<static> $query
     * @param Filter<static>  $filter
     */
    public function scopeForwarded(Builder $query, Filter $filter): void
    {
        $query->filtered($filter);
    }

    /** @return HasMany<Post, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class);
    }
}

class Comment extends Model
{
}

/** @param Builder<Post> $query */
function test(Builder $query, Post $post, PostFilter $postFilter, CommentFilter $commentFilter): void
{
    Post::filtered($postFilter);
    Post::query()->filtered($postFilter);
    $query->filtered($postFilter);
    $query->like($post);
    $query->sibling($post);
    $post->children()->filtered($postFilter);
    $query->filtered($commentFilter);
}

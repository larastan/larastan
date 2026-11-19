<?php

namespace ModelScopeAttributeStaticParameters;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
    #[Scope]
    protected function filtered(Builder $query, Filter $filter): void
    {
    }
}

class Comment extends Model
{
}

/** @param Builder<Post> $query */
function test(Builder $query, PostFilter $postFilter, CommentFilter $commentFilter): void
{
    Post::filtered($postFilter);
    $query->filtered($postFilter);
    $query->filtered($commentFilter);
}

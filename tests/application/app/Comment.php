<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    /** @phpstan-return MorphTo<Model, Comment> */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}

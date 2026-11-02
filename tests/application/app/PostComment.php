<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PostComment extends Model
{
    /** @return MorphTo<Post, $this> */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}

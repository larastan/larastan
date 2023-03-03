<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of Post
 * @extends Builder<TModelClass>
 */
class PostBuilder extends Builder
{
}

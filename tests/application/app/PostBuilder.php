<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Post
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModelClass>
 */
class PostBuilder extends Builder
{
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Collection<int, TModel>
 */
class OnlyValueGenericCollection extends Collection
{
}

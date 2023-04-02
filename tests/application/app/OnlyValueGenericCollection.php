<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends Collection<int, TModel>
 */
class OnlyValueGenericCollection extends Collection
{
}

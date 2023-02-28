<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

trait NestedSoftDeletes
{
    use SoftDeletes;
}

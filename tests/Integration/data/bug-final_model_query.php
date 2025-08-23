<?php

namespace BugFinalModelQuery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class User extends Model
{
    /** @return Builder<self> */
    public function test(): Builder
    {
        return self::query();
    }
}

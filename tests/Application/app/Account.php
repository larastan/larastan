<?php

namespace App;

use App\Traits\HasOwner;
use App\Traits\HasParent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasOwner;
    use HasParent;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }
}

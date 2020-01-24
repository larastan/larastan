<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }
}

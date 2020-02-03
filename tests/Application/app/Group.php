<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}

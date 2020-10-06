<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 */
class Group extends Model
{
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}

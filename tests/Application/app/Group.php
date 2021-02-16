<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 */
class Group extends Model
{
    use SoftDeletes;

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

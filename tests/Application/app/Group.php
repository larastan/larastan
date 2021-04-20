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

    /** @phpstan-return HasMany<Account> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /** @phpstan-return HasMany<User> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

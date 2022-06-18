<?php

namespace App;

use App\Traits\NestedSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 */
class Group extends Model
{
    use NestedSoftDeletes;

    /** @phpstan-return HasMany<Account, Group> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /** @phpstan-return HasMany<User, Group> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

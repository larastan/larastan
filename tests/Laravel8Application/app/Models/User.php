<?php

declare(strict_types=1);

namespace Laravel8\Models;

use App\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    /** @phpstan-return HasMany<Account> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(get_class($this));
    }
}

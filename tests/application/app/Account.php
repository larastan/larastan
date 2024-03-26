<?php

namespace App;

use App\Traits\HasOwner;
use App\Traits\HasParent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasOwner;
    use HasParent;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @phpstan-return BelongsTo<Group, Account> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withDefault();
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * @param  array<int, Account>  $models
     * @return AccountCollection<int, Account>
     */
    public function newCollection(array $models = []): AccountCollection
    {
        return new AccountCollection($models);
    }
}

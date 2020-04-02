<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tests\Application\HasManySyncable;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Account[] $accounts
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /** @var array<string, string> */
    protected $casts = ['meta' => 'array', 'blocked' => 'boolean'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function id(): int
    {
        return $this->id;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    /** @phpstan-return BelongsTo<Group> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function syncableRelation(): HasManySyncable
    {
        return $this->hasManySyncable(Account::class);
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo(null, 'model_type', 'model_id');
    }

    public function hasManySyncable($related, $foreignKey = null, $localKey = null): HasManySyncable
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManySyncable(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }
}

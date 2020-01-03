<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tests\Application\HasManySyncable;

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

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function syncableRelation(): HasManySyncable
    {
        return $this->hasManySyncable(Account::class);
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

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 */
class Team extends Model
{
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /** @return HasMany<Transaction> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<User> */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return ChildTeamBuilder
     */
    public static function query(): ChildTeamBuilder
    {
        return parent::query();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return ChildTeamBuilder
     */
    public function newEloquentBuilder($query): ChildTeamBuilder
    {
        return new ChildTeamBuilder($query);
    }
}

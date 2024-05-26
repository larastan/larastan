<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

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

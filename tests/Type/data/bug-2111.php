<?php

namespace Bug2111;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use function PHPStan\Testing\assertType;

class User extends Model
{
    /** @return BelongsToMany<Team, $this> */
    public function teams(): BelongsToMany
    {
        $relation = $this->belongsToMany(Team::class);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<Bug2111\Team, $this(Bug2111\User), Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $relation);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<Bug2111\Team, $this(Bug2111\User), Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $relation->latest());

        return $relation;
    }
    /** @return BelongsToMany<Team, $this> */
    public function paidTeams(): BelongsToMany
    {
        $relation = $this->teams()->whereHas('activeSubscriptions');
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<Bug2111\Team, $this(Bug2111\User), Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $relation);

        return $relation;
    }
}

class Team extends Model {}

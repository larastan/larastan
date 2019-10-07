<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Scopes extends Model
{
    public function testScopeAfterRelation() : HasOne
    {
        return $this->hasOne(User::class)->active();
    }

    public function testScopeAfterRelationWithHasMany() : HasMany
    {
        return $this->hasMany(User::class)->active();
    }
}

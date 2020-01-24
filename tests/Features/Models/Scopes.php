<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Scopes extends Model
{
    /** @var User */
    private $user;

    public function testScopeAfterRelation(): HasOne
    {
        return $this->hasOne(User::class)->active();
    }

    public function testScopeAfterRelationWithHasMany(): HasMany
    {
        return $this->hasMany(User::class)->active();
    }

    public function testScopeAfterQueryBuilderStaticCall(): Builder
    {
        return User::where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->active();
    }

    public function testScopeAfterQueryBuilderVariableCall(): Builder
    {
        $this->user = new User;

        return $this->user->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->where('foo', 'bar')->active();
    }

    public function testScopeWithFirst(): ?User
    {
        $this->user = new User;

        return $this->user->where('foo', 'bar')->active()->first();
    }
}

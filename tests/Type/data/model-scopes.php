<?php

namespace ModelScope;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

class Scopes extends Model
{
    /** @var User */
    private $user;

    public function testScopeAfterRelation()
    {
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\User>', $this->hasOne(User::class)->active());
    }

    public function testScopeAfterRelationWithHasMany()
    {
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\User>', $this->hasMany(User::class)->active());
    }

    public function testScopeAfterQueryBuilderStaticCall()
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active());
    }

    public function testScopeAfterQueryBuilderVariableCall()
    {
        $this->user = new User;

        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $this->user->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active());
    }

    public function testScopeWithFirst()
    {
        $this->user = new User;

        assertType('App\User|null', $this->user->where('name', 'bar')->active()->first());
    }

    public function testVoidScopeStillReturnsBuilder()
    {
        assertType('Illuminate\Database\Eloquent\Builder<ModelScope\Scopes>', $this->withVoidReturn());
    }

    public function testVoidScopeStillHasGenericBuilder()
    {
        assertType('ModelScope\Scopes|null', $this->withVoidReturn()->first());
    }

    /** @phpstan-param Builder<Scopes> $query */
    public function scopeWithVoidReturn(Builder $query): void
    {
        $query->where('whyuse', 'void');
    }

    public function testScopeThatStartsWithWordWhere()
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereActive());
    }

    public function testScopeDefinedInClassDocBlock(User $user): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->someScope());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->someScope());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->where('foo')->someScope());
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $user->where('foo')->someScope()->get());
    }
}

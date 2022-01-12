<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function PHPStan\Testing\assertType;

class Scopes extends Model
{
    /** @var User */
    private $user;

    /** @phpstan-return HasOne<User> */
    public function testScopeAfterRelation(): HasOne
    {
        return $this->hasOne(User::class)->active();
    }

    /** @phpstan-return HasMany<User> */
    public function testScopeAfterRelationWithHasMany(): HasMany
    {
        return $this->hasMany(User::class)->active();
    }

    /** @phpstan-return Builder<User> */
    public function testScopeAfterQueryBuilderStaticCall(): Builder
    {
        return User::where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active();
    }

    /** @phpstan-return Builder<User> */
    public function testScopeAfterQueryBuilderVariableCall(): Builder
    {
        $this->user = new User;

        return $this->user->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active();
    }

    public function testScopeWithFirst(): ?User
    {
        $this->user = new User;

        return $this->user->where('name', 'bar')->active()->first();
    }

    /** @phpstan-return Builder<Scopes> */
    public function testVoidScopeStillReturnsBuilder(): Builder
    {
        return $this->withVoidReturn();
    }

    public function testVoidScopeStillHasGenericBuilder(): ?Scopes
    {
        return $this->withVoidReturn()->first();
    }

    /** @phpstan-param Builder<Scopes> $query */
    public function scopeWithVoidReturn(Builder $query): void
    {
        $query->where('whyuse', 'void');
    }

    /** @phpstan-return Builder<User> */
    public function testScopeThatStartsWithWordWhere(): Builder
    {
        return User::query()->whereActive();
    }

    public function testScopeDefinedInClassDocBlock(User $user): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->someScope());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->someScope());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->where('foo')->someScope());
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $user->where('foo')->someScope()->get());
    }
}

<?php

namespace ModelScope;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

/** @param Builder<Model&ForTimeline> $intersection */
function test(Builder $intersection): void
{
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model&ModelScope\ForTimeline>', $intersection->where('foo', 'bar'));
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model&ModelScope\ForTimeline>', $intersection->forTimeline());
    assertType('Illuminate\Database\Eloquent\Model&ModelScope\ForTimeline', $intersection->forTimeline()->firstOrFail());
}

class Scopes extends Model
{
    private User $user;

    public function scopeWithVoidReturn(Builder $query): void
    {
        $query->where('whyuse', 'void');
    }

    /** @param Builder<Scopes> $query */
    public function test(User $user, Builder $query): void
    {
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\User, $this(ModelScope\Scopes)>', $this->hasOne(User::class)->active());
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\User, $this(ModelScope\Scopes)>', $this->hasMany(User::class)->active());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active());

        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $this->user->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active());
        assertType('App\User|null', $this->user->where('name', 'bar')->active()->first());
        assertType('Illuminate\Database\Eloquent\Builder<static(ModelScope\Scopes)>', $this->withVoidReturn());
        assertType('static(ModelScope\Scopes)|null', $this->withVoidReturn()->first());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereActive());

        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->someScope());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->someScope());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->where('foo')->someScope());
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $user->where('foo')->someScope()->get());
    }
}

interface ForTimeline
{
    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeForTimeline(Builder $query): Builder;
}

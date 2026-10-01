<?php

namespace ModelScope;

use App\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
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

    public function scopeCount(Builder $query): void
    {
        $query->whereNotNull('id');
    }

    /** @param Builder<Scopes> $query */
    public function test(User $user, Builder $query): void
    {
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<App\User, $this(ModelScope\Scopes)>', $this->hasOne(User::class)->active());
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\User, $this(ModelScope\Scopes)>', $this->hasMany(User::class)->active());
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active());

        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $this->user->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->where('name', 'bar')->active());
        assertType('App\User|null', $this->user->where('name', 'bar')->active()->first());
        assertType('Illuminate\Database\Eloquent\Builder<ModelScope\Scopes>', $this->withVoidReturn());
        assertType('ModelScope\Scopes|null', $this->withVoidReturn()->first());
        assertType('Illuminate\Database\Eloquent\Builder<ModelScope\Scopes>', self::query()->count());
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

/** @param Builder<Activity> $query */
function attributeScopes(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<ModelScope\Activity>', Activity::completed());
    assertType('Illuminate\Database\Eloquent\Builder<ModelScope\Activity>', $query->completed());
    assertType('ModelScope\Activity', $query->completed(false)->firstOrFail());
    assertType('Illuminate\Database\Eloquent\Builder<ModelScope\Activity>', Activity::withVoidReturn());
    assertType('Illuminate\Database\Eloquent\Builder<ModelScope\Activity>', $query->withVoidReturn());
    assertType('ModelScope\Activity', $query->withVoidReturn()->firstOrFail());
    assertType('*ERROR*', $query->notAScope());
}

class Activity extends Model
{
    /**
     * @param Builder<static> $query
     * @return Builder<static>
     */
    #[Scope]
    protected function completed(Builder $query, bool $completed = true): Builder
    {
        return $query->where('completed', $completed);
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function withVoidReturn(Builder $query): void
    {
        $query->where('whyuse', 'void');
    }

    /** @param Builder<static> $query */
    #[Scope]
    public function notAScope(Builder $query): void
    {
        $query->where('whyuse', 'void');
    }
}

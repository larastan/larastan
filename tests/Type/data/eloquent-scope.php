<?php

namespace EloquentScope;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

use function PHPStan\Testing\assertType;

/** @implements Scope<User> */
class ActiveUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder);
        assertType('App\User', $model);
    }
}

/**
 * An explicit Builder<User> parameter must not be a contravariance error.
 *
 * @implements Scope<User>
 */
class AnnotatedActiveUserScope implements Scope
{
    /**
     * @param Builder<User> $builder
     * @param User $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder);
        assertType('App\User', $model);
    }
}

function testAddGlobalScope(User $user): void
{
    $user->addGlobalScope(new ActiveUserScope());
    $user->addGlobalScope(static function (Builder $builder, Model $model): void {
        $builder->where('active', 1);
    });
    $user->addGlobalScope('active');
}

<?php

namespace BuilderOfType;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

/**
 * @param builder-of<\App\User> $userBuilder
 * @param builder-of<\App\Account> $accountBuilder
 * @param builder-of<\App\Team> $teamBuilder
 * @param builder-of<\App\User|\App\Team> $union
 */
function test($userBuilder, Builder $accountBuilder, Builder $teamBuilder, Builder $union): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userBuilder);
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $accountBuilder);
    assertType('App\ChildTeamBuilder', $teamBuilder);
    assertType('App\ChildTeamBuilder|Illuminate\Database\Eloquent\Builder<App\User>', $union);

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', genericMethod(\App\User::class));
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', genericMethod(\App\Account::class));
    assertType('App\ChildTeamBuilder', genericMethod(\App\Team::class));
}

/**
 * @template T of Model
 *
 * @param class-string<T> $class
 *
 * @return builder-of<T>
 */
function genericMethod(string $class): Builder
{
    return $class::query();
}

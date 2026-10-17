<?php

declare(strict_types=1);

namespace AggregateCallbackKeyRuleFixture;

use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @param Builder<User> $builder
 * @param Collection<int, User> $users
 * @param array<string, callable> $dynamicCallbacks
 */
function check(Builder $builder, User $user, Collection $users, string $dynamic, array $dynamicCallbacks): void
{
    // Legitimate heterogeneous callbacks are accepted.
    $builder->withCount([
        'posts' => static function (PostBuilder $query): void {},
        'accounts' => static function (Builder $query): void {},
    ]);

    // Broad callback type is valid for every compatible builder.
    $builder->withSum([
        'posts' => static function (Builder $query): void {},
        'accounts' => static function (Builder $query): void {},
    ], 'id');

    $builder->withCount([
        'posts' => static function (PostBuilder $query): void {},
        'accounts' => static function (PostBuilder $query): void {},
    ]);

    User::withSum(relations: [
        'posts as total_posts' => static function (Builder $query): void {},
        'accounts AS total_accounts' => static function (PostBuilder $query): void {},
    ], column: 'id');

    $user->loadCount([
        'accounts' => static function (PostBuilder $query): void {},
        'posts' => static function (PostBuilder $query): void {},
    ]);

    $users->loadExists([
        'posts' => static function (Builder $query): void {},
        'accounts' => static function (PostBuilder $query): void {},
    ]);

    // Dynamic/unresolved keys must not result in invented diagnostics.
    $builder->withCount([
        $dynamic => static function (PostBuilder $query): void {},
        'posts' => static function (PostBuilder $query): void {},
    ]);
    $builder->withCount($dynamicCallbacks);

    // Do not duplicate PHPStan's regular error when a callback fits no relation.
    $builder->withCount([
        'posts' => static function (int $query): void {},
        'accounts' => static function (Builder $query): void {},
    ]);

    // A missing relation is diagnosed independently; do not invent its builder.
    $builder->withCount([
        'posts' => static function (PostBuilder $query): void {},
        'nonexistent' => static function (PostBuilder $query): void {},
    ]);
}

/**
 * The parent model must be known, not merely one member of a union.
 *
 * @param Builder<User|\App\Account> $builder
 */
function ambiguousParentModel(Builder $builder): void
{
    $builder->withCount([
        'posts' => static function (PostBuilder $query): void {},
        'accounts' => static function (PostBuilder $query): void {},
    ]);
}

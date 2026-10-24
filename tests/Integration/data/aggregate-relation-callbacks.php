<?php

namespace AggregateRelationCallbacksIntegration;

use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @param Builder<User> $builder
 * @param Collection<int, User> $users
 */
function checkAggregateCallbackArguments(Builder $builder, User $user, Collection $users): void
{
    $builder->withCount(['posts' => static function (PostBuilder $query): void {}]);
    $builder->withCount(['posts as published_posts' => static function (PostBuilder $query): void {}]);
    $user->loadSum(['posts' => static function (PostBuilder $query): void {}], 'id');
    $users->loadExists(['posts' => static function (Builder $query): void {}]);

    // Mixed relation keys should allow distinct, correctly typed callbacks.
    $builder->withCount([
        'posts' => static function (PostBuilder $query): void {},
        'accounts' => static function (Builder $query): void {},
    ]);

    $builder->withCount(['posts' => static function (HasMany $query): void {}]);
    User::withCount(['posts as published_posts' => static function (int $query): void {}]);
    $user->loadCount(['posts' => static function (HasMany $query): void {}]);
    $users->loadSum(['accounts' => static function (int $query): void {}], 'id');
}

// Laravel Builder::callScope() accepts invokable objects as relation constraints.
final class PostAggregateConstraint
{
    /** @param PostBuilder<\App\Post> $query */
    public function __invoke(PostBuilder $query): void
    {
    }
}

/**
 * @param Builder<User> $builder
 * @param Collection<int, User> $users
 */
function validInvokableConstraints(Builder $builder, User $user, Collection $users): void
{
    $builder->withCount(['posts' => new PostAggregateConstraint()]);
    $user->loadCount(['posts' => new PostAggregateConstraint()]);
    $users->loadSum(['posts' => new PostAggregateConstraint()], 'id');
}

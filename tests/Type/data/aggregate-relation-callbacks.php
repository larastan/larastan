<?php

namespace AggregateRelationCallbacks;

use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use function PHPStan\Testing\assertType;

/**
 * @param Builder<User> $builder
 * @param 'posts'|'accounts' $relation
 */
function builderAggregates(Builder $builder, string $relation): void
{
    assertType('Illuminate\\Database\\Eloquent\\Builder<App\\User>', User::query()->withCount([
        'posts' => function ($query): void {
            assertType('App\\PostBuilder<App\\Post>', $query);
        },
    ]));

    User::withCount(['posts as published_posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }]);

    $builder->withCount(['posts AS total_posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }]);

    $builder->withSum(['accounts' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }], 'balance');

    $builder->withMax(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }], 'id');

    $builder->withMin(['accounts' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }], 'id');

    $builder->withAvg(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }], 'id');

    $builder->withExists(['accounts' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }]);

    $builder->withAggregate(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }], 'id', 'sum');

    $builder->withCount([$relation => function ($query): void {
        assertType('(App\\PostBuilder<App\\Post>|Illuminate\\Database\\Eloquent\\Builder<App\\Account>)', $query);
    }]);
}

/** @param Collection<int, User> $users */
function deferredAggregates(User $user, Collection $users): void
{
    assertType('App\\User', $user->loadCount(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }]));

    $user->loadMax(['accounts' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }], 'id');

    $user->loadMin(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }], 'id');

    $user->loadSum(['accounts as account_balance' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }], 'balance');

    $user->loadAvg(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }], 'id');

    $user->loadExists(['accounts' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }]);

    $user->loadAggregate(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }], 'id', 'sum');

    assertType('Illuminate\\Database\\Eloquent\\Collection<int, App\\User>', $users->loadCount(['posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }]));

    $users->loadSum(['accounts' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }], 'balance');

    $users->loadExists(['posts as published_posts' => function ($query): void {
        assertType('App\\PostBuilder<App\\Post>', $query);
    }]);

    $users->loadAggregate(['accounts' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Account>', $query);
    }], 'id', 'max');
}

function relatedModelBuilder(): void
{
    Post::query()->withCount(['comments' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Comment>', $query);
    }]);

    $post = new Post();
    $post->loadCount(['comments' => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<App\\Comment>', $query);
    }]);
}

/**
 * A nonconstant relationship name cannot establish the related model.
 *
 * @param Builder<User> $builder
 * @param literal-string $literal
 */
function dynamicAggregateNames(Builder $builder, string $dynamic, string $literal): void
{
    $builder->withCount([$dynamic => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<*>', $query);
    }]);

    $builder->withExists([$literal => function ($query): void {
        assertType('Illuminate\\Database\\Eloquent\\Builder<*>', $query);
    }]);
}

/** @param Builder<User> $builder */
function multipleAggregateCallbacks(Builder $builder): void
{
    $builder->withCount([
        'posts' => function ($query): void {
            assertType('(App\\PostBuilder<App\\Post>|Illuminate\\Database\\Eloquent\\Builder<App\\Account>)', $query);
        },
        'accounts' => function ($query): void {
            assertType('(App\\PostBuilder<App\\Post>|Illuminate\\Database\\Eloquent\\Builder<App\\Account>)', $query);
        },
    ]);
}

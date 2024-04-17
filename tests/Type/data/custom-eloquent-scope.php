<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Scope;

use function PHPStan\Testing\assertType;

/** @implements Scope<Illuminate\Database\Eloquent\Builder<App\User>,App\User> */
class UserScope implements Scope
{
    public function apply($builder, $model): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder);
        assertType('App\User', $model);
    }
}

/** @implements Scope<App\PostBuilder<App\Post>,App\Post> */
class PostScope implements Scope
{
    public function apply($builder, $model): void
    {
        assertType('App\PostBuilder<App\Post>', $builder);
        assertType('App\Post', $model);
    }
}

/** @implements Scope<App\NonGenericPostBuilder,App\Post> */
class PostWithNonGenericBuilderScope implements Scope
{
    public function apply($builder, $model): void
    {
        assertType('App\NonGenericPostBuilder', $builder);
        assertType('App\Post', $model);
    }
}

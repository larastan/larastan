<?php

namespace ModelWithRelations;

use App\User;

use function PHPStan\Testing\assertType;

/** @param 'accounts'|'group' $name */
function eagerLoad(User $user, string $dynamic, string $name): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with('accounts'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with(['accounts', 'group']));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with([]));

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with(['accounts' => function ($relation): void {
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $relation);
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $relation->where('active', true)->orderBy('id'));
    }]));

    $user->with(relations: ['group' => function ($relation): void {
        assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $relation);
    }]);

    User::with([
        'accounts' => function ($relation): void {
            assertType('(Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>)', $relation);
        },
        'group' => function ($relation): void {
            assertType('(Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>)', $relation);
        },
    ]);

    User::with(['accounts' => fn ($relation) => assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $relation)]);
    User::with(['posts.comments' => function ($relation): void {
        assertType('Illuminate\Database\Eloquent\Relations\MorphMany<App\Comment, App\Post>', $relation);
    }]);

    User::with([$name => function ($relation): void {
        assertType('(Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>)', $relation);
    }]);

    User::with([$dynamic => function ($relation): void {
        assertType('Illuminate\Database\Eloquent\Relations\Relation<*, *, *>', $relation);
        $relation->where('active', true);
    }]);

    User::with(['accounts', 'group' => function ($relation): void {
        assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $relation);
    }]);

    User::with(['accounts' => function ($relation): void {
        assertType('Illuminate\Database\Eloquent\Relations\Relation<*, *, *>', $relation);
    }, $dynamic => function ($relation): void {
        assertType('Illuminate\Database\Eloquent\Relations\Relation<*, *, *>', $relation);
    }]);
}

class ChildUser extends User
{
    public function insideClass(): void
    {
        static::with(['accounts' => function ($relation): void {
            assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, ModelWithRelations\ChildUser>', $relation);
        }]);
        $this->with(['group' => function ($relation): void {
            assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, ModelWithRelations\ChildUser>', $relation);
        }]);
    }
}

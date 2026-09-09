<?php

namespace RelationshipQueryCallbacks;

use App\Account;
use App\Comment;
use App\Post;
use App\PostComment;
use App\User;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

use function PHPStan\Testing\assertType;

/** @param 'posts'|'accounts' $relation */
function ordinaryRelationships(string $relation, string $unknown, User $user): void
{
    User::query()->whereHas('posts', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    User::query()->whereHas('posts.comments', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $query);
    });

    User::query()->whereHas($relation, function (Builder $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::query()->whereHas($unknown, function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereHas($user->posts(), function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    $builder = User::query();
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder->has('accounts'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder->doesntHave('accounts', callback: null));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder->whereDoesntHave('accounts'));

    $builder->orWhereHas(relation: 'posts.comments', count: 2, callback: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $query);
    });

    $builder->whereDoesntHave($user->accounts(), function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });
}

/**
 * @param class-string<Post> $postClass
 * @param list<class-string<Post|User>> $classes
 * @param list<string> $unknownTypes
 */
function morphRelationships(string $postClass, array $classes, string $unknown, array $unknownTypes, Comment $comment): void
{
    Comment::query()->whereHasMorph('commentable', Post::class, function (Builder $query, string $type) {
        assertType('App\PostBuilder<App\Post>', $query);
        assertType('string', $type);
    });

    Comment::query()->whereHasMorph('commentable', [Post::class, Account::class], function (Builder $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Comment::query()->whereHasMorph('commentable', $postClass, function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Comment::query()->whereHasMorph('commentable', $classes, function (Builder $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Comment::query()->whereHasMorph('commentable', '*', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Comment::query()->whereHasMorph('commentable', ['*'], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Comment::query()->whereHasMorph('commentable', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Comment::query()->whereHasMorph('commentable', 'post', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Comment::query()->whereHasMorph('commentable', $unknown, function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Comment::query()->whereHasMorph('commentable', $unknownTypes, function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Comment::query()->whereHasMorph('commentable', [Post::class, 'account'], function (Builder $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Comment::query()->whereHasMorph($comment->commentable(), Post::class, function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Comment::query()->whereHasMorph($comment->commentable(), '*', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });
}

/** @param list<string> $unknownTypes */
function declaredMorphTarget(string $unknown, array $unknownTypes, PostComment $comment): void
{
    PostComment::query()->whereHasMorph('commentable', '*', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    PostComment::query()->whereHasMorph('commentable', [], function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    PostComment::query()->whereHasMorph('commentable', 'post', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    PostComment::query()->whereHasMorph('commentable', $unknown, function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    PostComment::query()->whereHasMorph('commentable', $unknownTypes, function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    PostComment::query()->whereHasMorph($comment->commentable(), '*', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });
}

function morphCallForms(PostComment $comment): void
{
    PostComment::whereHasMorph(types: '*', callback: function (Builder $query, $type) {
        assertType('App\PostBuilder<App\Post>', $query);
        assertType('string', $type);
    }, relation: 'commentable');

    $comment->whereHasMorph('commentable', [Account::class, 'post'], function (Builder $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    PostComment::query()->hasMorph(callback: function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    }, types: Post::class, relation: 'commentable');

    PostComment::query()->whereHasMorph($comment->commentable(), Account::class, function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    assertType('Illuminate\Database\Eloquent\Builder<App\PostComment>', PostComment::hasMorph('commentable', Post::class));
    assertType('Illuminate\Database\Eloquent\Builder<App\PostComment>', PostComment::query()->doesntHaveMorph('commentable', '*', callback: null));
    assertType('Illuminate\Database\Eloquent\Builder<App\PostComment>', PostComment::whereDoesntHaveMorph('commentable', []));
}

/** @param 'accounts'|'group' $relation */
function eagerRelationships(User $user, string $relation, string $unknown): void
{
    User::query()->withWhereHas('posts', function (Builder|Relation $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\User, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $query);
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\User, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $query->where('active', true));
    });

    User::query()->withWhereHas('posts.comments', function (BuilderContract $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Comment>|Illuminate\Database\Eloquent\Relations\MorphMany<App\Comment, App\Post>', $query);
    });

    User::withWhereHas('accounts:id,user_id', function (Builder|Relation $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    });

    $user->withWhereHas(callback: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Group>|Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $query);
    }, relation: 'group');

    User::withWhereHas($relation, function (Builder|Relation $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>|Illuminate\Database\Eloquent\Builder<App\Group>|Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    });

    User::withWhereHas($unknown, function (Builder|Relation $query) {
        assertType('Illuminate\Database\Eloquent\Builder<*>|Illuminate\Database\Eloquent\Relations\Relation<*, *, *>', $query);
    });

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::withWhereHas('accounts'));
    assertType('App\PostBuilder<App\Post>', Post::query()->withWhereHas('comments', null));
}

/** @param 'posts'|'accounts' $relation */
function relationShortcuts(User $user, string $relation): void
{
    User::whereRelation('posts', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    User::query()->orWhereRelation('posts.comments', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $query);
    });

    $user->whereDoesntHaveRelation($user->posts(), function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    User::query()->orWhereDoesntHaveRelation(column: function ($query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    }, relation: $relation);

    User::withWhereRelation('posts', function (Builder|Relation $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\User, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $query);
    });

    User::query()->withWhereRelation(relation: 'posts.comments', column: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Comment>|Illuminate\Database\Eloquent\Relations\MorphMany<App\Comment, App\Post>', $query);
    });
}

function morphRelationShortcuts(PostComment $comment): void
{
    Comment::whereMorphRelation('commentable', Post::class, function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Comment::query()->orWhereMorphRelation('commentable', [Post::class, Account::class], function (Builder $query) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    $comment->whereMorphDoesntHaveRelation($comment->commentable(), '*', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    PostComment::query()->orWhereMorphDoesntHaveRelation(column: function ($query) {
        assertType('App\PostBuilder<App\Post>', $query);
    }, types: [], relation: 'commentable');
}

function directEagerCallbacks(string $unknown): void
{
    User::query()->with('accounts', function (Relation $query) {
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query->orderBy('id'));
    });

    User::query()->with('posts.comments:id,post_id', function (Relation $query) {
        assertType('Illuminate\Database\Eloquent\Relations\MorphMany<App\Comment, App\Post>', $query);
    });

    User::query()->with(callback: function ($query) {
        assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\Group, App\User>', $query);
    }, relations: 'group');

    User::query()->with($unknown, function (Relation $query) {
        assertType('Illuminate\Database\Eloquent\Relations\Relation<*, *, *>', $query);
    });
}

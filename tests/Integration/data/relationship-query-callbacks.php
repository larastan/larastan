<?php

namespace RelationshipQueryCallbackCompatibility;

use App\Comment;
use App\Post;
use App\PostBuilder;
use App\PostComment;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;

function ordinaryCallbacks(User $user): void
{
    User::query()->has('accounts', callback: function (Builder $query) {
        $query->whereKey(1);
    });

    User::doesntHave('posts', callback: function (PostBuilder $query) {
        $query->whereKey(1);
    });

    User::query()->whereHas($user->posts(), function (PostBuilder $query) {
        $query->whereKey(1);
    });

    User::query()->orWhereHas('posts.comments', function (Builder $query) {
        $query->whereKey(1);
    });

    User::query()->whereDoesntHave('accounts');
    User::orWhereDoesntHave('accounts', null);
    User::query()->whereHas('accounts', count: new Expression('1'));
}

function morphCallbacks(Comment $comment): void
{
    Comment::query()->whereHasMorph('commentable', [Post::class], function (PostBuilder $query, string $type) {
        $query->whereKey(1);
    });

    Comment::whereHasMorph($comment->commentable(), '*', function (Builder $query) {
        $query->whereKey(1);
    });

    Comment::query()->whereHasMorph('commentable', [], function (Builder $query) {
        $query->whereKey(1);
    });
}

function declaredMorphCallbacks(PostComment $comment, string $unknown): void
{
    PostComment::query()->whereHasMorph('commentable', '*', function (PostBuilder $query) {
        $query->whereKey(1);
    });

    PostComment::whereHasMorph('commentable', $unknown, function (PostBuilder $query) {
        $query->whereKey(1);
    });

    PostComment::whereHasMorph($comment->commentable(), Post::class, function (PostBuilder $query) {
        $query->whereKey(1);
    });
}

function invalidMorphCallbacks(): void
{
    Comment::whereHasMorph('commentable', Post::class, function (int $query) {});
    Comment::whereHasMorph('commentable', Post::class, function (Builder $query, int $type) {});
    Comment::whereHasMorph('commentable', User::class, function (PostBuilder $query) {});
}

function eagerCallbacks(): void
{
    User::withWhereHas('accounts', function (Builder|Relation $query) {
        $query->where('active', true);
    });

    User::query()->withWhereHas('posts.comments', function (Builder|Relation $query) {
        $query->whereKey(1);
    });

    User::withWhereHas(relation: 'posts', callback: function ($query) {
        $query->whereKey(1);
    }, count: new Expression('1'));

    User::withWhereHas('accounts');
    User::query()->withWhereHas('accounts', null);

    User::withWhereHas('accounts', function (Builder $query) {});
    User::query()->withWhereHas('accounts', function (Relation $query) {});
}

function shortcutCallbacks(User $user, Comment $comment): void
{
    User::whereRelation('posts', fn (PostBuilder $query) => $query->whereKey(1));
    User::query()->orWhereRelation($user->posts(), fn (PostBuilder $query) => $query->whereKey(1));
    User::whereDoesntHaveRelation('posts', fn (PostBuilder $query) => $query->whereKey(1));
    User::orWhereDoesntHaveRelation('posts', fn (PostBuilder $query) => $query->whereKey(1));

    Comment::whereMorphRelation('commentable', Post::class, fn (PostBuilder $query) => $query->whereKey(1));
    Comment::query()->orWhereMorphRelation('commentable', [Post::class], fn (PostBuilder $query) => $query->whereKey(1));
    Comment::whereMorphDoesntHaveRelation('commentable', Post::class, fn (PostBuilder $query) => $query->whereKey(1));
    Comment::orWhereMorphDoesntHaveRelation($comment->commentable(), Post::class, fn (PostBuilder $query) => $query->whereKey(1));

    User::withWhereRelation('posts', fn (PostBuilder|Relation $query) => $query->whereKey(1));
    User::withWhereHas('posts', fn (PostBuilder|Relation $query) => $query->whereKey(1));
    User::withWhereHas('posts:id', fn (PostBuilder|Relation $query) => $query->whereKey(1));
    User::query()->with('accounts', fn (Relation $query) => $query->where('id', 1));

    User::whereRelation('accounts', 'id', 1);
    User::orWhereRelation('accounts', ['id' => 1]);
    User::whereDoesntHaveRelation('accounts', new Expression('id'), '=', 1);
    User::orWhereDoesntHaveRelation('accounts', 'id', '>', 1);
    User::withWhereRelation('accounts', 'id', 1);
    Comment::whereMorphRelation('commentable', Post::class, 'id', 1);
    Comment::orWhereMorphRelation('commentable', Post::class, ['id' => 1]);
    Comment::whereMorphDoesntHaveRelation('commentable', Post::class, new Expression('id'), '=', 1);
    Comment::orWhereMorphDoesntHaveRelation('commentable', Post::class, 'id', '>', 1);
    User::query()->with('accounts', 'posts');
}

function invalidShortcutCallbacks(): void
{
    User::whereRelation('accounts', function (PostBuilder $query) {});
    Comment::whereMorphRelation('commentable', Post::class, function (int $query) {});
    Comment::whereMorphRelation('commentable', Post::class, function (PostBuilder $query, string $type) {});
    User::withWhereRelation('accounts', function (Builder $query) {});
    User::query()->with('accounts', function (Builder $query) {});
}

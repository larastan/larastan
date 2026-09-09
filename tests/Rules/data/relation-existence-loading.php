<?php

declare(strict_types=1);

namespace RelationExistenceLoading;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @param Collection<int, User> $users */
function loading(User $user, Collection $users, string $name): void
{
    $user->load('missing');
    $user->loadMissing('accounts.missing');
    $user->load(['accounts.transactions', 'missing']);
    $user->load(['accounts' => ['missing' => static function () {}]]);
    $user->load(['missing' => static function () {}, 'accounts']);
    $user->load('accounts:id,user_id', 'missing:id');
    $user->loadMissing('accounts', 'missing');
    $user->loadCount('missing as total');
    $user->loadCount('accounts', 'missing');
    $user->loadAggregate('missing AS total', 'id', 'sum');
    $user->loadMax('missing', 'id');
    $user->loadMin('missing', 'id');
    $user->loadSum(['missing as total' => static function () {}], 'id');
    $user->loadAvg('missing', 'id');
    $user->loadExists('missing');
    $users->load('missing');
    $users->loadMissing('accounts.missing');
    $users->loadCount('missing as total');
    User::withCount('missing as total');
    User::withSum('missing', 'id');
    User::withOnly('missing');
    User::orWhereRelation('missing', 'id', 1);
    $user->loadCount('accounts.transactions');
    $user->loadCount('accounts  as  total');
    $user->load('accounts as total');

    $user->load(['accounts:id' => ['transactions:id']]);
    $user->loadMissing(['accounts.transactions:id', 'group:id']);
    $user->load(['accounts' => static function () {}, 'group']);
    $user->loadCount(['accounts AS total' => static function () {}]);
    $user->loadSum('accounts as total', 'id');
    $user->loadExists('accounts');
    $users->load('accounts.transactions');
    $users->loadCount('accounts as total');
    $users->has('not_a_relation');
    $user->load($name);
    $user->loadMissing([$name]);
    $user->loadCount([$name => static function () {}]);
    $user->loadSum($name, 'id');
    User::with(['accounts' => $name]);
    User::with('accounts', static function () {});
}

function unknownModel(Model $model): void
{
    $model->load('unknown');
}

function otherApis(User $user): void
{
    User::withAggregate('missing', 'id', 'max');
    User::withMax('missing', 'id');
    User::withMin('missing', 'id');
    User::withAvg('missing', 'id');
    User::withExists('missing');
    User::withWhereRelation('missing', 'id', 1);
    User::whereDoesntHaveRelation('missing', 'id', 1);
    User::orWhereDoesntHaveRelation('missing', 'id', 1);
    User::hasMorph('missing', [User::class]);
    User::orHasMorph('missing', [User::class]);
    User::doesntHaveMorph('missing', [User::class]);
    User::orDoesntHaveMorph('missing', [User::class]);
    User::whereHasMorph('missing', [User::class]);
    User::orWhereHasMorph('missing', [User::class]);
    User::whereDoesntHaveMorph('missing', [User::class]);
    User::orWhereDoesntHaveMorph('missing', [User::class]);
    User::whereMorphRelation('missing', [User::class], 'id', 1);
    User::orWhereMorphRelation('missing', [User::class], 'id', 1);
    User::whereMorphDoesntHaveRelation('missing', [User::class], 'id', 1);
    User::orWhereMorphDoesntHaveRelation('missing', [User::class], 'id', 1);
    $user->loadSum(column: 'id', relations: 'missing as total');
    $user->LOAD('missing');
    $user->load(['missing' => ['nested']]);
    $user->load('save');

    $user->loadSum(column: 'id', relations: 'accounts AS total');
    User::withSum(column: 'id', relation: 'accounts');
    $user->loadCount('accounts:id');
    $user->load(...);
    User::withCount('accounts', static function () {});
}

/** @param User|\App\Post $model */
function ambiguousModel($model): void
{
    $model->load('unknown');
}

function unrelatedReceiver(): void
{
    $receiver = new class {
        public function load(string $name): void {}
    };
    $receiver->load('unknown');
}

function nullableModel(?User $user): void
{
    $user?->load('missing');
    $user?->load('accounts');
}

function unknownRelatedModel(\App\Comment $comment): void
{
    $comment->load('commentable.anything');
    $comment->load(['commentable.anything', 'missing']);
}

/** @param array<string> $names */
function dynamicList(User $user, array $names): void
{
    $user->load($names);
    $user->load(...$names);
}

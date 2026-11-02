<?php

namespace RelationCursorPaginate;

use App\User;
use Illuminate\Pagination\Cursor;

function belongsToMany(User $user, Cursor $cursor): void
{
    $user->roles()->cursorPaginate(cursor: $cursor);
    $user->roles()->cursorPaginate(15, ['*'], 'cursor', $cursor);
    $user->roles()->cursorPaginate(cursor: 'eyJpZCI6MX0');
    $user->roles()->cursorPaginate(cursor: null);
}

function hasManyThrough(User $user, Cursor $cursor): void
{
    $user->transactions()->cursorPaginate(cursor: $cursor);
    $user->transactions()->cursorPaginate(15, ['*'], 'cursor', $cursor);
    $user->transactions()->cursorPaginate(cursor: 'eyJpZCI6MX0');
    $user->transactions()->cursorPaginate(cursor: null);
}

function hasOneThrough(User $user, Cursor $cursor): void
{
    $user->car()->cursorPaginate(cursor: $cursor);
}

function builderAndHasMany(User $user, Cursor $cursor): void
{
    User::query()->cursorPaginate(cursor: $cursor);
    $user->accounts()->cursorPaginate(cursor: $cursor);
}

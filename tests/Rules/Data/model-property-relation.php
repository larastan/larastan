<?php

/** @var \App\User $user */
$user = \App\User::findOrFail(1);

$user->accounts()->where('foo', 'bar');
$user->accounts()->create(['foo' => 'bar']);
$user->accounts()->firstOrNew(['foo' => 'bar']);
$user->accounts()->firstOrCreate(['foo' => 'bar']);
$user->accounts()->updateOrCreate(['foo' => 'bar']);

$user->posts()->where('foo', 'bar');

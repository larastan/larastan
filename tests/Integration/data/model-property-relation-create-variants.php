<?php

/** @var \App\User $user */
$user->accounts()->createQuietly(['foo' => 'bar']);
$user->accounts()->forceCreate(['foo' => 'bar']);
$user->accounts()->forceCreateQuietly(['foo' => 'bar']);
$user->accounts()->createManyQuietly([['foo' => 'bar']]);

$user->accounts()->createQuietly(['name' => 'foo']);
$user->accounts()->forceCreate(['name' => 'foo']);
$user->accounts()->forceCreateQuietly(['name' => 'foo']);
$user->accounts()->createManyQuietly([['name' => 'foo']]);
$user->accounts()->createQuietly();
$user->accounts()->forceCreateQuietly();

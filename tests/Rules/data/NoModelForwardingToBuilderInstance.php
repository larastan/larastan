<?php

/** @var \App\User $user */
$user = \App\User::find(1);
$user->first();
$user->get();
$user->find();
$user->paginate();
$user->where('id', 1);
$user->take(1);
$user->max('foo');


<?php

/** @var \App\User $user */
$user->save(['touch' => false]);
$user->save(['touch' => 'no']);
$user->save();

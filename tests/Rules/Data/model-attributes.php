<?php

use App\User;

$user       = new User();
$user->name = 'Foo Bar';
$user->foo  = 'bar';

function getFoo(User $user): string
{
    return $user->bar;
}

function getEmail(User $user): string
{
    return $user->email;
}

$bar  = (new User())->baz;
$boop = (new User())->meta;

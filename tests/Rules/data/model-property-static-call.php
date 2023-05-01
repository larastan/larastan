<?php

namespace Tests\Rules\data;

$foo = \App\User::class;

$foo::create([
    'foo' => 'bar',
]);

function foo(\App\User $foo): \App\User
{
    return $foo::create([
        'foo' => 'bar',
    ]);
}

\App\User::create([
    'foo' => 'bar',
]);

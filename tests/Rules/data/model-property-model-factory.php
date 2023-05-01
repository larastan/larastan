<?php

namespace Tests\Rules\data;

function foo(): void
{
    \App\User::factory()->createOne(['foo' => 'bar']);
}

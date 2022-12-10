<?php

namespace ModelPropertyModelFactory;

function foo(): void
{
    \App\User::factory()->createOne(['foo' => 'bar']);
}

<?php

function foo(): void
{
    \App\User::factory()->createOne(['foo' => 'bar']);
}

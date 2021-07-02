<?php

function foo(): void
{
    \Laravel8\Models\User::factory()->createOne(['foo' => 'bar']);
}

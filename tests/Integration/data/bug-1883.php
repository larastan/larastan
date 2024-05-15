<?php

use Illuminate\Support\Facades\Facade;

/**
 * @mixin RedisAlias
 */
class RedisFacade extends Facade
{
}


function test(): void
{
    RedisFacade::noSuchMethod();
}

<?php

namespace RedisDriverPredis;

use Illuminate\Support\Facades\Redis;

use function PHPStan\Testing\assertType;

function test(): void
{
    // #907 — predis return types differ from phpredis
    assertType('int', Redis::hexists('h', 'field'));
    assertType('string|null', Redis::zscore('z', 'member'));

    $connection = Redis::connection();
    assertType('Illuminate\Redis\Connections\PredisConnection', $connection);
}

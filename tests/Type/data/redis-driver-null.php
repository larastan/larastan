<?php

namespace RedisDriverNull;

use Illuminate\Support\Facades\Redis;

use function PHPStan\Testing\assertType;

function test(): void
{
    // testbench database.redis.client defaults to phpredis, so null resolves there
    assertType('array<int, mixed>|Redis', Redis::pipeline(function ($pipe): void {
        $pipe->set('foo', 'bar');
    }));
    assertType('Illuminate\Redis\Connections\PhpRedisConnection', Redis::connection());
}

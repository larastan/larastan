<?php

namespace RedisDriverPhpRedis;

use Illuminate\Support\Facades\Redis;

use function PHPStan\Testing\assertType;

function test(): void
{
    // #2251 / #2622 — Laravel PhpRedisConnection pipeline/transaction
    assertType('array<int, mixed>|Redis', Redis::pipeline(function ($pipe): void {
        $pipe->set('foo', 'bar');
    }));
    assertType('array<int, mixed>|Redis', Redis::transaction(fn ($transaction) => $transaction->incr('foo')));
    assertType('array<int, mixed>|Redis', Redis::pipeline());
    assertType('array<int, mixed>|Redis', Redis::transaction());

    // #907 — Laravel set arity (not ext-redis's 2–3 arg set)
    assertType('bool', Redis::set('key', 'value', 'EX', 60));
    assertType('bool', Redis::set('key', 'value', 'EX', 60, 'NX'));

    // #907 — phpredis return types (via PhpRedisConnection @mixin \Redis)
    assertType('(bool|Redis)', Redis::hexists('h', 'field'));
    assertType('(float|Redis|false)', Redis::zscore('z', 'member'));

    // #2453 — Laravel eval order: script, numKeys, ...args
    assertType('mixed', Redis::eval('return 1', 0));
    assertType('mixed', Redis::eval('return redis.call("get", KEYS[1])', 1, 'mykey'));

    // #14 smoke
    assertType('(int|Redis|false)', Redis::publish('channel', 'message'));
    assertType('array', Redis::hmget('h', ['field1', 'field2']));
    assertType('(bool|Redis)', Redis::expire('foo', 3));

    $connection = Redis::connection();
    assertType('Illuminate\Redis\Connections\PhpRedisConnection', $connection);
    assertType('array<int, mixed>|Redis', $connection->pipeline(function ($pipe): void {
        $pipe->get('foo');
    }));
}

<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class Facades
{
    public function testEventAssertDispatched(): void
    {
        Event::assertDispatched('FooEvent');
    }

    public function testEventAssertDispatchedTimes(): void
    {
        Event::assertDispatchedTimes('FooEvent', 5);
    }

    public function testEventAssertNotDispatched(): void
    {
        Event::assertNotDispatched('FooEvent');
    }

    /** @return mixed[] */
    public function testRedisFacadeLRangeMethod(): array
    {
        return Redis::lrange('some-key', 0, -1);
    }

    /** @return mixed[] */
    public function testRedisFacadeConnection(): array
    {
        $redis = Redis::connection();

        return $redis->lrange('some-key', 0, -1);
    }

    public function testRedisFacadeExpire(): bool
    {
        return Redis::expire('foo', 3);
    }

    /** @return mixed[] */
    public function testRedisHmget(): array
    {
        return Redis::hmget('h', ['field1', 'field2']);
    }

    public function testDBQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::query();
    }

    public function testDBTransactionLevel(): int
    {
        return DB::transactionLevel();
    }

    public function testQueueCreatePayloadUsing(): void
    {
        Queue::createPayloadUsing(function () {
        });
    }

    public function testGetLogger(): \Psr\Log\LoggerInterface
    {
        return Log::getLogger();
    }
}

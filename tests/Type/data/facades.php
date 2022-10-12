<?php

namespace Facades;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Http\Request', Request::instance());

assertType('void', Event::assertDispatched('FooEvent'));
assertType('void', Event::assertDispatchedTimes('FooEvent', 5));
assertType('void', Event::assertNotDispatched('FooEvent'));

$redis = Redis::connection();
assertType('array', Redis::lrange('some-key', 0, -1));
assertType('array', $redis->lrange('some-key', 0, -1));
assertType('bool', Redis::expire('foo', 3));
assertType('array', Redis::hmget('h', ['field1', 'field2']));

assertType('Illuminate\Database\Query\Builder', DB::query());
assertType('int', DB::transactionLevel());

assertType('void', Queue::createPayloadUsing(function () {
}));

assertType('Psr\Log\LoggerInterface', Log::getLogger());

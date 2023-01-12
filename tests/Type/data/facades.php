<?php

namespace Facades;

use App\DummyFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use function PHPStan\Testing\assertType;

function foo()
{
    assertType('Illuminate\Http\Request', Request::instance());

    assertType('void', Event::assertDispatched('FooEvent'));
    assertType('void', Event::assertDispatchedTimes('FooEvent', 5));
    assertType('void', Event::assertNotDispatched('FooEvent'));

    $redis = Redis::connection();
    assertType('array', $redis->lrange('some-key', 0, -1));
    assertType('array', Redis::lrange('some-key', 0, -1));
    assertType('bool', Redis::expire('foo', 3));
    assertType('array', Redis::hmget('h', ['field1', 'field2']));

    assertType('Illuminate\Database\Query\Builder', DB::query());
    assertType('int', DB::transactionLevel());

    assertType('void', Queue::createPayloadUsing(function () {
    }));

    assertType('Psr\Log\LoggerInterface', Log::getLogger());

    assertType('Illuminate\Filesystem\FilesystemAdapter', Storage::disk());
    assertType('Illuminate\Filesystem\FilesystemAdapter', Storage::drive());
    assertType('bool', Storage::disk()->deleteDirectory('foo'));
    assertType('bool', Storage::drive()->deleteDirectory('foo'));
    assertType('string|false', Storage::putFile('foo', 'foo/bar'));
    assertType('string|false', Redis::get('foo'));

    assertType('string', DummyFacade::foo());
    assertType('int', DummyFacade::bar());
}

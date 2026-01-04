<?php

namespace Facades;

use App\DummyFacade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\Http\Request', Request::instance());

    assertType('null', Event::assertDispatched('FooEvent'));
    assertType('null', Event::assertDispatchedTimes('FooEvent', 5));
    assertType('null', Event::assertNotDispatched('FooEvent'));

    $redis = Redis::connection();
    assertType('(array<mixed>|Redis|false)', $redis->lrange('some-key', 0, -1));
    assertType('(array<mixed>|Redis|false)', Redis::lrange('some-key', 0, -1));
    assertType('(bool|Redis)', Redis::expire('foo', 3));
    assertType('(array<string, mixed>|Redis|false)', Redis::hmget('h', ['field1', 'field2']));

    assertType('Illuminate\Database\Query\Builder', DB::query());
    assertType('int', DB::transactionLevel());

    assertType('null', Queue::createPayloadUsing(function () {
    }));

    assertType('Psr\Log\LoggerInterface', Log::getLogger());

    assertType('Illuminate\Filesystem\FilesystemAdapter', Storage::disk());
    assertType('Illuminate\Filesystem\FilesystemAdapter', Storage::drive());
    assertType('Illuminate\Filesystem\FilesystemAdapter', Storage::cloud());
    assertType('bool', Storage::disk()->deleteDirectory('foo'));
    assertType('bool', Storage::drive()->deleteDirectory('foo'));
    assertType('bool', Storage::cloud()->deleteDirectory('foo'));
    assertType('string|false', Storage::putFile('foo', 'foo/bar'));
    assertType('mixed', Redis::get('foo'));
    assertType('mixed', Redis::client());

    assertType('string', DummyFacade::foo());
    assertType('int', DummyFacade::bar());

    assertType('Illuminate\Http\Client\Response', Http::get('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::post('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::put('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::patch('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::delete('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::head('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::send('GET', 'https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::timeout(30)->get('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::withHeaders(['X-Foo' => 'bar'])->post('https://example.test'));

    assertType('GuzzleHttp\Promise\PromiseInterface', Http::async()->get('https://example.test'));
    assertType('GuzzleHttp\Promise\PromiseInterface', Http::async()->post('https://example.test'));
    assertType('GuzzleHttp\Promise\PromiseInterface', Http::async()->send('GET', 'https://example.test'));
    assertType('GuzzleHttp\Promise\PromiseInterface', Http::timeout(30)->async()->get('https://example.test'));
    assertType('GuzzleHttp\Promise\PromiseInterface', Http::withHeaders(['X-Foo' => 'bar'])->async()->post('https://example.test'));

    assertType('Illuminate\Contracts\Cache\Repository', Cache::driver());
    assertType('\'123\'', Cache::remember(
        key: 'cache-key',
        ttl: 60,
        callback: static fn (): string => '123',
    ));
    assertType('123', Cache::rememberForever(
        key: 'cache-key',
        callback: static fn (): int => 123,
    ));
}

<?php

namespace FacadesL1241;

use App\DummyFacade;
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
    assertType('Illuminate\Http\Client\Response', Http::get('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::post('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::put('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::patch('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::delete('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::head('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::send('GET', 'https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::timeout(30)->get('https://example.test'));
    assertType('Illuminate\Http\Client\Response', Http::withHeaders(['X-Foo' => 'bar'])->post('https://example.test'));

    assertType('Illuminate\Http\Client\Promises\LazyPromise', Http::async()->get('https://example.test'));
    assertType('Illuminate\Http\Client\Promises\LazyPromise', Http::async()->post('https://example.test'));
    assertType('Illuminate\Http\Client\Promises\LazyPromise', Http::async()->send('GET', 'https://example.test'));
    assertType('Illuminate\Http\Client\Promises\LazyPromise', Http::timeout(30)->async()->get('https://example.test'));
    assertType('Illuminate\Http\Client\Promises\LazyPromise', Http::withHeaders(['X-Foo' => 'bar'])->async()->post('https://example.test'));

}

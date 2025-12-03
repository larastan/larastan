<?php

namespace PendingRequestReturnTypes;

use Illuminate\Http\Client\PendingRequest;

use function PHPStan\Testing\assertType;

function testSyncCalls(PendingRequest $request): void
{
    assertType('Illuminate\Http\Client\Response', $request->get('https://example.com'));
    assertType('Illuminate\Http\Client\Response', $request->post('https://example.com', []));
    assertType('Illuminate\Http\Client\Response', $request->put('https://example.com', []));
    assertType('Illuminate\Http\Client\Response', $request->patch('https://example.com', []));
    assertType('Illuminate\Http\Client\Response', $request->delete('https://example.com'));
    assertType('Illuminate\Http\Client\Response', $request->head('https://example.com'));
    assertType('Illuminate\Http\Client\Response', $request->send('GET', 'https://example.com'));
}

function testChainedSyncCalls(PendingRequest $request): void
{
    assertType('Illuminate\Http\Client\Response', $request->timeout(30)->get('https://example.com'));
    assertType('Illuminate\Http\Client\Response', $request->withHeaders(['X-Foo' => 'bar'])->post('https://example.com', []));
}

function testAsyncCalls(PendingRequest $request): void
{
    // async() calls should return the union type (we return null to use original)
    assertType('GuzzleHttp\Promise\PromiseInterface|Illuminate\Http\Client\Response', $request->async()->get('https://example.com'));
    assertType('GuzzleHttp\Promise\PromiseInterface|Illuminate\Http\Client\Response', $request->async()->post('https://example.com', []));
}

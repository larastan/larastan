<?php

namespace RedisFacade;

use Illuminate\Support\Facades\Redis;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('array<mixed>|Redis', Redis::pipeline(function ($pipe): void {
        $pipe->set('foo', 'bar');
    }));
    assertType('array<mixed>|Redis', Redis::transaction(fn ($transaction) => $transaction->incr('foo')));
    assertType('array<mixed>|Redis', Redis::pipeline());
    assertType('array<mixed>|Redis', Redis::transaction());
}

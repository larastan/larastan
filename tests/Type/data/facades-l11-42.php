<?php

namespace FacadesL1142;

use Illuminate\Support\Facades\Redis;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('array', Redis::hmget('h', ['field1', 'field2']));
    assertType('string|null', Redis::get('foo'));
}

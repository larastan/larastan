<?php

namespace Benchmark;

use Illuminate\Support\Benchmark;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('float', Benchmark::measure(fn () => 'Hello World'));
    assertType('array<int, float>', Benchmark::measure([fn () => 'Hello World', fn () => 'Hello World']));
    assertType('array<string, float>', Benchmark::measure(['test1' => fn () => 'Hello World', 'test2' => fn () => 'Hello World']));
    assertType('array<int|string, float>', Benchmark::measure(['test' => fn () => 'Hello World', 100 => fn () => 'Hello World']));
}

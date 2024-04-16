<?php

declare(strict_types=1);

namespace RequestHeader;

use Illuminate\Http\Request;

use function PHPStan\Testing\assertType;

function test(Request $request): void
{
    assertType('array<string, array<int, string|null>>', $request->header());
    assertType('string|null', $request->header('key'));
    assertType('string', $request->header('key', 'default'));
}

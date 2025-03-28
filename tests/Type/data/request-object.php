<?php

declare(strict_types=1);

namespace RequestObject;

use Illuminate\Http\Request;

use function PHPStan\Testing\assertType;

function test(Request $request): void
{
    assertType('array<int, Illuminate\Http\UploadedFile>', $request->file());
    assertType('Illuminate\Http\UploadedFile|null', $request->file('foo'));
    assertType('array<int, Illuminate\Http\UploadedFile>|Illuminate\Http\UploadedFile|stdClass', $request->file('foo', new \stdClass()));
    assertType('Illuminate\Routing\Route|null', $request->route());
    assertType('(object|string|null)', $request->route('foo'));
    assertType('(object|string)', $request->route('foo', 'bar'));
    assertType('array<string, string>', $request->server());
    assertType('string|null', $request->server('foo'));
    assertType('int|string', $request->server('foo', 5));
}

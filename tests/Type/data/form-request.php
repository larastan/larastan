<?php

declare(strict_types=1);

namespace FormRequest;

use App\Http\Requests\FooRequest;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

function test(FormRequest $request, FooRequest $fooRequest): void
{
//    assertType('Illuminate\Support\ValidatedInput', $request->safe());
//    assertType('array{key: mixed}', $request->safe(['key']));
//    assertType('array<string, mixed>', $request->validated());

    assertType('string', $fooRequest->name);
    assertType('int', $fooRequest->age);
    assertType("1|'1'|'on'|'true'|'yes'|true", $fooRequest->newsletter);
}

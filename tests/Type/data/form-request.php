<?php

declare(strict_types=1);

namespace FormRequest;

use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

function test(FormRequest $request): void
{
    assertType('Illuminate\Support\ValidatedInput', $request->safe());
    assertType('array{key: mixed}', $request->safe(['key']));
    assertType('array<string, mixed>', $request->validated());
}

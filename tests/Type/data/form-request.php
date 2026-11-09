<?php

declare(strict_types=1);

namespace FormRequest;

use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

function test(FormRequest $request, bool $condition): void
{
    assertType('Illuminate\Support\ValidatedInput', $request->safe());
    assertType('array{key: mixed}', $request->safe(['key']));
    assertType('array{key: mixed}|array{other: mixed, another: mixed}', $request->safe($condition ? ['key'] : ['other', 'another']));
    assertType('array<string, mixed>', $request->validated());
}

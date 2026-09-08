<?php

declare(strict_types=1);

namespace FormRequestNumericPaths;

use App\Http\Requests\SelectorRequest;

use function PHPStan\Testing\assertType;

function testNumericSelectors(SelectorRequest $request): void
{
    assertType('array{}', $request->safe(['0']));
    assertType('array{}', $request->safe(['-1']));
    assertType('array{}', $request->safe(['01', '+1', '-01']));
    assertType('array{name: non-empty-string}', $request->safe(['-1', 'name', '0']));
    assertType('null', $request->validated('0'));
    assertType('42', $request->validated('-1', 42));
}

function testNumericArrayKeys(SelectorRequest $request): void
{
    assertType('array{numeric?: array{0?: mixed}}', $request->safe(['numeric.0']));
    assertType('array{}', $request->safe(['numeric.-1']));
}

function testNumericSegments(SelectorRequest $request): void
{
    assertType('array{-1: array{name: non-empty-string, ...}, ...}', $request->negative);
    assertType('array{array{name: non-empty-string, ...}, ...}', $request->zero);
    assertType("array{'01': array{name: non-empty-string, ...}, ...}", $request->leadingZero);
    assertType('array{-1: array{name: non-empty-string}}', $request->validated('negative'));
    assertType('array{array{name: non-empty-string}}', $request->validated('zero'));
    assertType("array{'01': array{name: non-empty-string}}", $request->validated('leadingZero'));
    assertType("array{'+1': array{name: non-empty-string}}", $request->validated('stringPlus'));
    assertType("array{'-0': array{name: non-empty-string}}", $request->validated('stringNegativeZero'));
    assertType("array{'-01': array{name: non-empty-string}}", $request->validated('stringNegativeLeadingZero'));
}

<?php

declare(strict_types=1);

namespace ValidationRulesBeforeLaravel12_21;

use App\Http\Requests\StrictValidationRequest;

use function PHPStan\Testing\assertType;

function test(StrictValidationRequest $request): void
{
    assertType("0|1|'0'|'1'|bool", $request->booleanValue);
    assertType('(float|int|numeric-string)', $request->numericValue);
    assertType('(float|int|numeric-string)', $request->integerValue);
    assertType('(float|int|numeric-string)', $request->integerInValue);
    assertType('float|int<1, 20>|numeric-string|null', $request->boundedInteger);
    assertType('float|int<10, 15>|numeric-string|null', $request->repeatedBounds);
    assertType('3|float|numeric-string|null', $request->exactInteger);
    assertType('float|int<2, max>|numeric-string|null', $request->constrainedInteger);
    assertType('float|int|numeric-string|null', $request->contradictoryBounds);
}

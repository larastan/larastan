<?php

declare(strict_types=1);

namespace ValidationRulesBeforeLaravel12_21;

use App\Http\Requests\StrictValidationRequest;

use function PHPStan\Testing\assertType;

function test(StrictValidationRequest $request): void
{
    assertType("0|1|'0'|'1'|bool", $request->booleanValue);
    assertType('(float|int|numeric-string)', $request->numericValue);
    assertType('(float|int|numeric-string|true)', $request->integerValue);
    assertType('(float|int|numeric-string|true)', $request->integerInValue);
    assertType('float|int|numeric-string|true|null', $request->boundedInteger);
    assertType('float|int|numeric-string|true|null', $request->repeatedBounds);
    assertType('float|int|numeric-string|true|null', $request->exactInteger);
    assertType('float|int|numeric-string|true|null', $request->constrainedInteger);
    assertType('float|int|numeric-string|true|null', $request->contradictoryBounds);
}

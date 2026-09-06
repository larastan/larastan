<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_21;

use App\Http\Requests\StrictValidationRequest;

use function PHPStan\Testing\assertType;

function test(StrictValidationRequest $request): void
{
    assertType('bool', $request->booleanValue);
    assertType('float|int', $request->numericValue);
    // Laravel 12.21 ignores the strict parameter for integer validation.
    assertType('(float|int|numeric-string|true)', $request->integerValue);
    assertType('(float|int|numeric-string|true)', $request->integerInValue);
    assertType('float|int|numeric-string|true|null', $request->boundedInteger);
    assertType('float|int|numeric-string|true|null', $request->repeatedBounds);
    assertType('float|int|numeric-string|true|null', $request->exactInteger);
    assertType('float|int|numeric-string|true|null', $request->constrainedInteger);
    assertType('float|int|numeric-string|true|null', $request->contradictoryBounds);
}

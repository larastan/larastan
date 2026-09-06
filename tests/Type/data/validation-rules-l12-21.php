<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_21;

use App\Http\Requests\StrictValidationRequest;

use function PHPStan\Testing\assertType;

function test(StrictValidationRequest $request): void
{
    assertType('bool', $request->booleanValue);
    assertType('float|int', $request->numericValue);
    assertType('(int|numeric-string)', $request->integerValue);
    assertType('(int|numeric-string)', $request->integerInValue);
    assertType('int|numeric-string|null', $request->boundedInteger);
    assertType('int|numeric-string|null', $request->repeatedBounds);
    assertType('int|numeric-string|null', $request->exactInteger);
    assertType('int|numeric-string|null', $request->constrainedInteger);
    assertType('int|numeric-string|null', $request->contradictoryBounds);
}

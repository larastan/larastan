<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_22;

use App\Http\Requests\StrictValidationRequest;

use function PHPStan\Testing\assertType;

function test(StrictValidationRequest $request): void
{
    assertType('bool', $request->booleanValue);
    assertType('float|int', $request->numericValue);
    assertType('int', $request->integerValue);
    assertType('0|1', $request->integerInValue);
    assertType('int<1, 20>|null', $request->boundedInteger);
    assertType('int<10, 15>|null', $request->repeatedBounds);
    assertType('3|null', $request->exactInteger);
    assertType('2|3|null', $request->constrainedInteger);
    assertType('int|null', $request->contradictoryBounds);
}

<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_22;

use App\Http\Requests\StrictValidationRequest;

use function PHPStan\Testing\assertType;

function test(StrictValidationRequest $request): void
{
    assertType('(1|2)', $request->priority);
    assertType('bool', $request->booleanValue);
    assertType('float|int', $request->numericValue);
    assertType('0|1', $request->integerInValue);
    assertType('int<10, 15>|null', $request->repeatedBounds);

    assertType('float|int', $request->numericInValue);
    assertType('float|int', $request->numericObjectInValue);
    assertType('bool', $request->booleanInValue);
}

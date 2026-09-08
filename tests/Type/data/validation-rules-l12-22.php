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
    assertType('int', $request->integerValue);
    assertType('0|1', $request->integerInValue);
    assertType('int<1, 20>|null', $request->boundedInteger);
    assertType('int<10, 15>|null', $request->repeatedBounds);
    assertType('3|null', $request->exactInteger);
    assertType('2|3|null', $request->constrainedInteger);
    assertType('int|null', $request->contradictoryBounds);

    assertType('float|int', $request->numericInValue);
    assertType('float|int', $request->numericObjectInValue);
    assertType('bool', $request->booleanInValue);
    assertType('-1|0|1', $request->signedIntegerInValue);
    assertType('int', $request->decimalIntegerValue);
    assertType('int', $request->noncanonicalIntegerValue);
    assertType(
        'array{booleanValue: bool, numericValue: float|int, integerValue: int, integerInValue: 0|1, boundedInteger?: int<1, 20>, repeatedBounds?: int<10, 15>, exactInteger?: 3, constrainedInteger?: 2|3, contradictoryBounds?: int, numericInValue: float|int, numericObjectInValue: float|int, booleanInValue: bool, signedIntegerInValue: -1|0|1, decimalIntegerValue: int, noncanonicalIntegerValue: int, priority: (1|2)}',
        $request->validated(),
    );
}

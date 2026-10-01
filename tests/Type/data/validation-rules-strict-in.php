<?php

declare(strict_types=1);

namespace ValidationRulesStrictIn;

use App\Http\Requests\AdditionalRulesRequest;
use App\Http\Requests\FooRequest;

use function PHPStan\Testing\assertType;

function test(AdditionalRulesRequest $request, FooRequest $fooRequest): void
{
    assertType("'0'", $request->requiredZero);
    assertType("'1'|'2'", $request->stringNumericInValue);
    assertType("'1'|'draft'", $request->stringMixedInValue);
    assertType("'1'|'1.5'", $fooRequest->primitiveState);
}

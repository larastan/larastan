<?php

declare(strict_types=1);

namespace ValidationRulesLooseIn;

use App\Http\Requests\AdditionalRulesRequest;
use App\Http\Requests\FooRequest;

use function PHPStan\Testing\assertType;

function test(AdditionalRulesRequest $request, FooRequest $fooRequest): void
{
    assertType('numeric-string', $request->requiredZero);
    assertType('numeric-string', $request->stringNumericInValue);
    assertType("'draft'|numeric-string", $request->stringMixedInValue);
    assertType('numeric-string', $fooRequest->primitiveState);
}

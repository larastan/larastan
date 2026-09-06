<?php

declare(strict_types=1);

namespace ValidationRulesStrictIn;

use ValidationRules\AdditionalRulesRequest;

use function PHPStan\Testing\assertType;

function test(AdditionalRulesRequest $request): void
{
    assertType("'1'|'2'", $request->stringNumericInValue);
    assertType("'1'|'draft'", $request->stringMixedInValue);
}

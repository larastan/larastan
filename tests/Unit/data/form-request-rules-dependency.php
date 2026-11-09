<?php

namespace FormRequestRulesDependency;

use App\Http\Requests\SafeReturnRequest;
use App\Http\Requests\TraitRules\StringRequest;

/** @return array<mixed> */
function readsValidatedData(SafeReturnRequest $class, StringRequest $trait): array
{
    return [$class->validated(), $trait->validated()];
}

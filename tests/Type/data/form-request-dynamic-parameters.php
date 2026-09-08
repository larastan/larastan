<?php

declare(strict_types=1);

namespace FormRequestDynamicParameters;

use App\Http\Requests\DynamicParametersRequest;

use function PHPStan\Testing\assertType;

function testDynamicParameters(DynamicParametersRequest $request): void
{
    assertType(
        'array<array{sourceUrl: mixed, originalTitle: non-empty-string, removalRequestReason: non-empty-string, ...}>',
        $request->work,
    );
    assertType('non-empty-string', $request->validated('title'));
    assertType('non-empty-string', $request->splitName);
    assertType('non-empty-string', $request->interpolatedName);
    assertType('non-empty-string', $request->email);
    assertType('non-empty-string', $request->boundedEmail);
    assertType('non-empty-string', $request->validated('boundedEmail'));
    assertType('non-empty-string', $request->formattedDate);
    assertType('float|int|numeric-string', $request->decimal);
    assertType('float|int|non-empty-string', $request->pattern);
    assertType('non-empty-string', $request->choice);
    assertType("'alpha'|'beta'", $request->limitedChoice);
    assertType('array{name?: string, ...}', $request->validated('record'));
    assertType('array{name?: string, other?: mixed}', $request->validated('limitedRecord'));
    assertType('list', $request->values);
    assertType('string|null', $request->nullableValue);
    assertType('non-empty-string|null', $request->optionalValue);
    assertType('non-empty-string', $request->requiredValue);
    assertType('mixed', $request->excludedValue);
    assertType('non-empty-string|null', $request->validated('excludedValue'));
    assertType('float|int|numeric-string', $request->integerValue);
    assertType('float|int|numeric-string', $request->numericValue);
    assertType("0|1|'0'|'1'|bool", $request->booleanValue);
    assertType('non-empty-string', $request->boundedValue);
    assertType('mixed', $request->unknownRule);
    assertType('non-empty-string', $request->pipeParameter);
    assertType('mixed', $request->wholeString);
    assertType('mixed', $request->variableRules);
    assertType('mixed', $request->helperRules);
}

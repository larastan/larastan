<?php

declare(strict_types=1);

namespace FormRequestAllowedKeys;

use App\Http\Requests\AllowedKeysRequest;

use function PHPStan\Testing\assertType;

function testAllowedKeys(AllowedKeysRequest $request): void
{
    assertType('array{name?: string, other?: mixed}', $request->validated('object'));
    assertType('array{name?: string, other?: mixed}', $request->validated('string'));
    assertType('array{name: non-empty-string, other?: mixed}', $request->validated('requiredChild'));
    assertType('array{name: array{first: non-empty-string, ...}}', $request->validated('nested'));
    assertType('array{name: array{first?: string, ...}}', $request->validated('requiredNested'));
    assertType('array{name: array{first: non-empty-string, other?: mixed}}', $request->validated('nestedAllowed'));
    assertType('array{name?: string}|null', $request->validated('pruned'));
    assertType('array{name?: string, other?: mixed}', $request->pruned);
    assertType('array{other?: mixed}', $request->validated('excluded'));
    assertType('null', $request->validated('excluded.name'));
    assertType('array{name?: string, other?: mixed}', $request->validated('conditional'));
    assertType('array{other?: mixed}', $request->validated('lastExcluded'));
    assertType('array{missing?: string}|null', $request->validated('partialExcluded'));
    assertType('array{name?: string, other?: mixed}|null', $request->validated('conditionalPruning'));
    assertType('array{name?: string, other?: mixed}|null', $request->validated('dynamicPruning'));
    assertType('array{name?: string}|null', $request->validated('emptyPruning'));
    assertType('array{name?: string}|null', $request->validated('literalPruning'));
    assertType('array{name?: string, ...}|null', $request->validated('unknownKeys'));
    assertType('array{name?: string, ...}', $request->validated('nonEmptyKeys'));
    assertType('array{name: array{first: non-empty-string, ...}, ...}', $request->validated('unknownNested'));
    assertType('array{kept: non-empty-string, ...}', $request->validated('unknownExcluded'));
    assertType('array{0?: mixed, 1?: mixed}', $request->validated('numeric'));
    assertType("array{'first,last'?: mixed}", $request->validated('quoted'));
    assertType('array{first?: mixed, last?: mixed}', $request->validated('serialized'));
}

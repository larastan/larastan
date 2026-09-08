<?php

declare(strict_types=1);

namespace FormRequestExclusion;

use App\Http\Requests\AllowedKeysRequest;

use function PHPStan\Testing\assertType;

function testExcludedChildren(AllowedKeysRequest $request): void
{
    assertType('array', $request->validated('payload'));
    assertType('array{name?: string, ...}|null', $request->validated('excludedConditional'));
    assertType('array{missing?: string}|null', $request->validated('sibling'));
    assertType('null', $request->validated('sibling.name'));
    assertType('array{meta?: array{missing?: string}}|null', $request->validated('deepSibling'));
    assertType('array', $request->validated('subtree'));
    assertType('array{meta?: mixed, ...}', $request->validated('excludedNested'));
    assertType('array{}', $request->validated('elements'));
    assertType('array', $request->validated('items'));
    assertType('array<array>|null', $request->validated('arrays'));
    assertType('array{}|null', $request->validated('unruled'));
}

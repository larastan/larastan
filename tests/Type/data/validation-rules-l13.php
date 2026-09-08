<?php

declare(strict_types=1);

namespace ValidationRulesLaravel13;

use App\Http\Requests\ArrayKeysRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(ArrayKeysRequest $request): void
{
    assertType(
        "Illuminate\\Validation\\Rules\\ArrayKeys<array{'name', 'email'}>",
        Rule::arrayKeys(['name', 'email']),
    );
    assertType('array{name: non-empty-string, email?: mixed}', $request->payload);
    assertType('array{name: non-empty-string, email?: mixed}', $request->validated('payload'));
    assertType('array{name: non-empty-string, email?: mixed}', $request->validated('stringPayload'));
    assertType('array{name?: string, email?: mixed}', $request->validated('optionalChild'));
    assertType('array{name?: string}|null', $request->validated('pruned'));
    assertType('array{0?: mixed, 1?: mixed}', $request->validated('numeric'));
    assertType('array', $request->tags);
    assertType('array', $request->filteredTags);
}

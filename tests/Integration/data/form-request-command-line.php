<?php

declare(strict_types=1);

namespace FormRequestCommandLine;

use App\Http\Requests\AccessorRequest;

use function PHPStan\Testing\assertType;

function test(AccessorRequest $request): void
{
    assertType('non-empty-string', $request->name);
    assertType('non-empty-string', $request->validated('name'));
}

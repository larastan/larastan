<?php

declare(strict_types=1);

namespace RequestHeader;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Http\Request $request */
assertType('array<string, array<int, string|null>>', $request->header());
assertType('string|null', $request->header('key'));
assertType('string', $request->header('key', 'default'));

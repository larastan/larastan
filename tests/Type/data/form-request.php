<?php

declare(strict_types=1);

namespace FormRequest;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Foundation\Http\FormRequest $request */
assertType('Illuminate\Support\ValidatedInput', $request->safe());
assertType('array<string, mixed>', $request->safe(['key']));

<?php

declare(strict_types=1);

namespace ValidatorSafe;

use Illuminate\Validation\Validator;

use function PHPStan\Testing\assertType;

function test(Validator $validator): void
{
    assertType('Illuminate\Support\ValidatedInput', $validator->safe());
    assertType('array<string, mixed>', $validator->safe(['key']));
}

<?php

declare(strict_types=1);

namespace ValidatorSafe;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Validation\Validator $validator */
assertType('Illuminate\Support\ValidatedInput', $validator->safe());
assertType('array<string, mixed>', $validator->safe(['key']));

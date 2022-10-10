<?php

namespace OptionalType;

use Illuminate\Support\Optional;
use function optional;
use function PHPStan\Testing\assertType;
use StdClass;

$arrayOptional = optional(['foo' => 'bar']);

assertType(Optional::class, $arrayOptional);

$callbackOptional = optional('1', function (string $value): int {
    return 1;
});

assertType('int', $callbackOptional);

$nullCallbackOptional = optional(null, function (string $value): int {
    return 1;
});

assertType('null', $nullCallbackOptional);

$objectOptional = optional(new StdClass());

assertType(Optional::class, $objectOptional);
assertType('mixed', $objectOptional->foo);

$nullOptional = optional();

assertType(Optional::class, $nullOptional);

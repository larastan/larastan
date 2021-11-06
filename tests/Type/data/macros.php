<?php

namespace Macros;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

try {
    Request::validate([]);
} catch (ValidationException $e) {
    $foo = 'foo';
}

assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

<?php

namespace Macros;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

try {
    Request::validate([]);
} catch (ValidationException $e) {
    $foo = 'foo';
}

assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

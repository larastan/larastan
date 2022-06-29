<?php

namespace Macros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

try {
    Request::validate([]);
} catch (ValidationException $e) {
    $foo = 'foo';
}

Builder::globalCustomMacro(b: 99);

assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

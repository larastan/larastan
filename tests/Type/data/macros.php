<?php

namespace Macros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

try {
    Request::validate([]);
} catch (ValidationException $e) {
    $foo = 'foo';
}

assertType('string', Builder::globalCustomMacro(b: 99));

assertType('int', Route::facadeMacro());

assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

<?php

namespace Macros;

use App\Post;
use App\PostBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPStan\TrinaryLogic;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

try {
    Request::validate([]);
} catch (ValidationException $e) {
    $foo = 'foo';
}

assertType('string', Builder::globalCustomMacro(b: 99));
assertType('string', Post::globalCustomMacro(b: 99));
assertType('string', PostBuilder::globalCustomMacro(b: 99));

assertType('int', Route::facadeMacro());
assertType('int', Auth::sessionGuardMacro());
assertType('int', Auth::requestGuardMacro());

assertType('string', collect([])->customCollectionMacro());
assertType('string', Collection::customCollectionMacro());

assertType('string', Str::trimMacro(''));
assertType('string', Str::asciiAliasMacro(''));

assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

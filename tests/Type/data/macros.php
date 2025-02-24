<?php

namespace Macros;

use App\Post;
use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\ValidationException;
use PHPStan\TrinaryLogic;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function test(): void
{
    try {
        Request::validate([]);
    } catch (ValidationException $e) {
        $foo = 'foo';
    }

    assertType('string', Builder::globalCustomMacro(b: 99));
    assertType('string', Post::globalCustomMacro(b: 99));
    assertType('string', PostBuilder::globalCustomMacro(b: 99));
    assertType('string', User::first()->accounts()->globalCustomMacro(b: 99));

    assertType('string', \Illuminate\Database\Query\Builder::globalCustomDatabaseQueryMacro(b: 99));
    assertType('string', Post::globalCustomDatabaseQueryMacro(b: 99));
    assertType('string', PostBuilder::globalCustomDatabaseQueryMacro(b: 99));
    assertType('string', User::first()->accounts()->globalCustomDatabaseQueryMacro(b: 99));

    assertType('int', Route::facadeMacro());
    assertType('int', Auth::sessionGuardMacro());
    assertType('int', Auth::requestGuardMacro());

    assertType('string', collect([])->customCollectionMacro());
    assertType('string', Collection::customCollectionMacro());

    assertType('string', collect([])->customCollectionMacroString());
    assertType('string', Collection::customCollectionMacroString());

    assertType('string', Str::trimMacro(''));
    assertType('string', Str::asciiAliasMacro(''));

    assertType('mixed', Cache::rememberIf(true, 'key', 60, fn () => 'value'));

    assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

    Request::macro('foo', function () {
        assertType(Request::class, $this);
    });

    Foo::macro('foo', function () {
        assertType(Foo::class, $this);
    });
}

class Foo
{
    use Macroable;
}

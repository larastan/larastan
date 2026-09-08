<?php

declare(strict_types=1);

namespace FormRequestTraitRules\Consumer;

use App\Http\Requests\TraitRules\InheritedRequest;
use App\Http\Requests\TraitRules\IntegerRequest;
use App\Http\Requests\TraitRules\StringRequest;

use function PHPStan\Testing\assertType;

function test(StringRequest $string, IntegerRequest $integer, InheritedRequest $inherited): void
{
    assertType('non-empty-string', $string->local);
    assertType('non-empty-string', $string->imported);
    assertType('non-empty-string', $string->function);
    assertType("'first'|'second'", $string->choice);
    assertType('non-empty-string', $string->self);
    assertType('non-empty-string', $string->static);
    assertType("'App\\\\Traits\\\\FormRequestRules'", $string->namespace);
    assertType('non-empty-string', $string->validated('local'));
    assertType('non-empty-string', $inherited->validated('local'));
    assertType('non-empty-string', $inherited->self);
    assertType('non-empty-string', $inherited->static);
    assertType('non-empty-string', $integer->validated('local'));
    assertType('float|int|numeric-string', $integer->self);
    assertType('float|int|numeric-string', $integer->static);
}

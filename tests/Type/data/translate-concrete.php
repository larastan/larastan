<?php

namespace TranslateConcrete;

use function PHPStan\Testing\assertType;

function test(?string $key): void
{
    assertType('Illuminate\Translation\Translator', trans());
    assertType('Illuminate\Translation\Translator', trans(null));
    assertType('Illuminate\Translation\Translator', trans(key: null));
    assertType('Illuminate\Translation\Translator', trans(locale: 'en'));
    assertType('Illuminate\Translation\Translator', trans(...[null]));
    assertType('Illuminate\Translation\Translator', trans(...[null, [], 'en']));
    assertType('Illuminate\Translation\Translator', trans(...[]));
    assertType('array|Illuminate\Translation\Translator|string', trans($key));
}

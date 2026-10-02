<?php

namespace TranslateContract;

use function PHPStan\Testing\assertType;

function test(?string $key): void
{
    assertType('Illuminate\Contracts\Translation\Translator', trans());
    assertType('Illuminate\Contracts\Translation\Translator', trans(null));
    assertType('Illuminate\Contracts\Translation\Translator', trans(key: null));
    assertType('Illuminate\Contracts\Translation\Translator', trans(locale: 'en'));
    assertType('Illuminate\Contracts\Translation\Translator', trans(...[null]));
    assertType('Illuminate\Contracts\Translation\Translator', trans(...[null, [], 'en']));
    assertType('Illuminate\Contracts\Translation\Translator', trans(...[]));
    assertType('array<mixed>|Illuminate\Contracts\Translation\Translator|string', trans($key));
}

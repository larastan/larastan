<?php

namespace Translator;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\Translator;

use function PHPStan\Testing\assertType;

function test(Translator $trans, TranslatorContract $transContract): void
{
    assertType('(array|string)', $trans->get('language.string'));
    assertType('(array|string)', $transContract->get('language.string'));
}

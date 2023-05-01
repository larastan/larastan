<?php

namespace Translator;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\Translator;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Translation\Translator $trans */
assertType('(array|string)', $trans->get('language.string'));

/** @var \Illuminate\Contracts\Translation\Translator $transContract */
assertType('(array|string)', $transContract->get('language.string'));

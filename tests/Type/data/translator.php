<?php

namespace Translator;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\Translator;

use function PHPStan\Testing\assertType;

/** @var Translator $trans */
assertType('(array|string)', $trans->get('language.string'));

/** @var TranslatorContract $transContract */
assertType('(array|string)', $transContract->get('language.string'));

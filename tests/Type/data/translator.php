<?php

namespace Translator;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\Translator;

use function PHPStan\Testing\assertType;

/** @var 'language.string'|'language.array' $key */

/** @var Translator $trans */
assertType('string', $trans->get('language.string'));
assertType('string', $trans->get('language.absent'));
assertType('array', $trans->get('language.array'));
assertType('string', $trans->get('language.array.key'));
assertType('array|string', $trans->get($key));

/** @var TranslatorContract $transContract */
assertType('string', $transContract->get('language.string'));
assertType('string', $transContract->get('language.absent'));
assertType('array', $transContract->get('language.array'));
assertType('string', $transContract->get('language.array.key'));
assertType('array|string', $transContract->get($key));

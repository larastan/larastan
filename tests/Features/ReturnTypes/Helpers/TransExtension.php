<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Contracts\Translation\Translator;

class TransExtension
{
    /**
     * @return mixed
     */
    public function testTrans()
    {
        return trans('foo');
    }

    public function testTranslator(): Translator
    {
        return trans();
    }
}

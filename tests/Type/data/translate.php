<?php

declare(strict_types=1);

namespace Translate;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\Contracts\Translation\Translator', trans());
    assertType('(array|string)', trans('foo'));
    assertType('(array|string)', trans('Hi :name', ['name' => 'Niek']));

    assertType('null', __());
    assertType('(array|string)', __('foo'));
    assertType('(array|string)', __('Hi :name', ['name' => 'Niek']));
}

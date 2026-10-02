<?php

declare(strict_types=1);

namespace Translate;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('(array|string)', trans('foo'));
    assertType('(array|string)', trans(key: 'foo'));
    assertType('(array|string)', trans(...['foo', [], 'en']));
    assertType('(array|string)', trans('Hi :name', ['name' => 'Niek']));

    assertType('null', __());
    assertType('(array|string)', __('foo'));
    assertType('(array|string)', __('Hi :name', ['name' => 'Niek']));
}

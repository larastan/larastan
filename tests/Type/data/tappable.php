<?php

namespace Tappable;

use Illuminate\Support\Traits\Tappable;

use function PHPStan\Testing\assertType;

class TappableClass
{
    use Tappable;
}

function test(): void
{
    assertType(
        'Tappable\TappableClass',
        (new TappableClass())->tap(function (TappableClass $tappable) {
        }),
    );

    assertType(
        'Illuminate\Support\HigherOrderTapProxy<Tappable\TappableClass>',
        (new TappableClass())->tap(),
    );
}

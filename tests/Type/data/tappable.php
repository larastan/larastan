<?php

namespace Tappable;

use Illuminate\Support\Traits\Tappable;

use function PHPStan\Testing\assertType;

class TappableClass
{
    use Tappable;
}

assertType(
    'Tappable\TappableClass',
    (new TappableClass())->tap(function (TappableClass $tappable) {
    }),
);

assertType(
    'Illuminate\Support\HigherOrderTapProxy<Tappable\TappableClass>',
    (new TappableClass())->tap(),
);

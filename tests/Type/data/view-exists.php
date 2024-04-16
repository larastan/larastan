<?php

namespace IlluminateView;

use function PHPStan\Testing\assertType;

function test(string $view): void
{
    if (view()->exists($view)) {
        assertType('view-string', $view);
    }

    assertType('string', $view);
}

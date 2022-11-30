<?php

namespace IlluminateView;

use function PHPStan\Testing\assertType;

/** @var string $view */
if (view()->exists($view)) {
    assertType('view-string', $view);
}

assertType('string', $view);

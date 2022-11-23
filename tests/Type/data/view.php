<?php

namespace IlluminateView;

use function PHPStan\Testing\assertType;

assertType('Illuminate\Contracts\View\Factory', view());
assertType('Illuminate\Contracts\View\View', view('foo'));
assertType('Illuminate\Contracts\View\View', view('foo')->with('bar', 'baz'));
assertType('Illuminate\Contracts\View\View', view('foo')->withFoo('bar'));

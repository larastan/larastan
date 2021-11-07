<?php

namespace RedirectResponse;

use Illuminate\Http\RedirectResponse;
use function PHPStan\Testing\assertType;

assertType(RedirectResponse::class, redirect()->back()->withSuccess(true));
assertType(RedirectResponse::class, redirect()->back()->withCookie('foo'));

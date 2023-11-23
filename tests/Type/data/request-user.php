<?php

declare(strict_types=1);

namespace RequestObject;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Http\Request $request */
assertType('App\User|null', $request->user());
assertType('App\User|null', $request->user('web'));
assertType('App\Admin|null', $request->user('admin'));

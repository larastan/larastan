<?php

namespace ModelProperties;

use function PHPStan\Testing\assertType;

/** @var \App\User $user */
assertType('array', $user->l11_cast);

<?php

namespace ModelProperties;

use App\User;
use function PHPStan\Testing\assertType;

/** @var User $user */
assertType('int', $user->newStyleAttribute);
assertType('int', $user->stringButInt);
assertType('string', $user->email);

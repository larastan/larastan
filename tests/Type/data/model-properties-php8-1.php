<?php

namespace ModelProperties;

use App\Casts\BackedEnumeration;
use App\Casts\BasicEnumeration;
use App\User;

use function PHPStan\Testing\assertType;

/** @var User $user */
assertType(BasicEnumeration::class, $user->basic_enum);
assertType(BackedEnumeration::class, $user->backed_enum);

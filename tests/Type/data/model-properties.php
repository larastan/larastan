<?php

namespace ModelProperties;

use App\User;
use function PHPStan\Testing\assertType;

/** @var User $user */
assertType('int', $user->newStyleAttribute);
assertType('int', $user->stringButInt);
assertType('string', $user->email);
assertType('array', $user->allowed_ips);
assertType('string', $user->floatButRoundedDecimalString);

// CastsAttributes
assertType('App\ValueObjects\Favorites', $user->favorites);

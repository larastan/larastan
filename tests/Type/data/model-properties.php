<?php

namespace ModelProperties;

use App\User;
use Carbon\Carbon;
use function PHPStan\Testing\assertType;

/** @var User $user */
assertType('int', $user->newStyleAttribute);
assertType('int', $user->stringButInt);
assertType('string', $user->email);
assertType('array', $user->allowed_ips);
assertType('string', $user->floatButRoundedDecimalString);
assertType(Carbon::class, $user->email_verified_at);

// CastsAttributes
assertType('App\ValueObjects\Favorites', $user->favorites);

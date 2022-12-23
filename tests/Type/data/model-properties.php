<?php

namespace ModelProperties;

use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

/** @var User $user */
assertType('int', $user->newStyleAttribute);
assertType('int', $user->stringButInt);
assertType('string', $user->email);
assertType('array', $user->allowed_ips);
assertType('numeric-string', $user->floatButRoundedDecimalString);

// Model Casts
assertType('int', $user->int);
assertType('int', $user->integer);
assertType('float', $user->real);
assertType('float', $user->float);
assertType('float', $user->double);
assertType('numeric-string', $user->decimal);
assertType('string', $user->string);
assertType('bool', $user->bool);
assertType('bool', $user->boolean);
assertType('stdClass', $user->object);
assertType('array', $user->array);
assertType('array', $user->json);
assertType(Collection::class, $user->collection);
assertType(Carbon::class, $user->date);
assertType(Carbon::class, $user->datetime);
assertType(CarbonImmutable::class, $user->immutable_date);
assertType(CarbonImmutable::class, $user->immutable_datetime);
assertType('int', $user->timestamp);
assertType('\'active\'|\'inactive\'', $user->enum_status);

// CastsAttributes
assertType('App\ValueObjects\Favorites', $user->favorites);

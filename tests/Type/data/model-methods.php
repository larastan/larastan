<?php

namespace ModelRelations;

use App\User;

use function PHPStan\Testing\assertType;

/** @var User $user */
assertType('array{name: string, email: string}', $user->only('name', 'email'));
assertType('array{name: string, email: string}', $user->only(['name', 'email']));
assertType('array{name: string, nonexistent: null}', $user->only(['name', 'nonexistent']));
assertType('array<string, mixed>', $user->only(['name', $foo]));

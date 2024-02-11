<?php

namespace ModelMethods;

use App\User;

use function PHPStan\Testing\assertType;

/** @var User $user */
assertType('array{name: string, email: string}', $user->only('name', 'email'));
assertType('array{name: string, email: string}', $user->only(['name', 'email']));
assertType('array{name: string, nonexistent: null}', $user->only(['name', 'nonexistent']));
assertType('array<string, mixed>', $user->only(['name', $foo]));

$columns = ['name', 'email'];
$foo = 'nonexistent';
assertType('array{name: string, email: string}', $user->only(...$columns));
assertType('array{name: string, email: string}', $user->only($columns));
assertType('array{nonexistent: null}', $user->only($foo));
assertType(
    'array{name: string, email: string, nonexistent: null}',
    $user->only([...$columns, $foo]),
);

<?php

namespace EloquentGetterTypes;

use App\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('array<string, string>', $user->getCasts());
    assertType('list<string>', $user->getTouchedRelations());
}

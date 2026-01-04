<?php

namespace Bug2188;

use App\UuidModel;
use App\UlidModel;
use App\Role;

use function PHPStan\Testing\assertType;

function test(UuidModel $uuidModel, UlidModel $ulidModel, Role $role): void
{
    assertType('string', $uuidModel->id);
    assertType('string', $ulidModel->id);
    assertType('string', $role->id);
}

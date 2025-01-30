<?php

use App\UlidModel;
use App\UuidModel;

use function PHPStan\Testing\assertType;

function test(UlidModel $ulid, UuidModel $uuid): void
{
    assertType('string', $ulid->id);
    assertType('string', $uuid->id);
}

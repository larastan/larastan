<?php

namespace Passthru;

use App\User;

use function PHPStan\Testing\assertType;

function test() {
    assertType('int', User::query()->getCountForPagination());
}

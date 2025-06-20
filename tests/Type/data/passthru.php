<?php

namespace Passthru;

use App\User;

use function PHPStan\Testing\assertType;

function test() {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::getCountForPagination());
}

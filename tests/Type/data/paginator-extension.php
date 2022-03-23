<?php

declare(strict_types=1);

namespace PaginatorExtension;

use App\User;
use function PHPStan\Testing\assertType;

assertType('array', User::paginate()->all());
assertType('array', User::simplePaginate()->all());
assertType('array', User::cursorPaginate()->all());

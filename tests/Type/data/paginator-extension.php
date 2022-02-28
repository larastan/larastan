<?php

declare(strict_types=1);

namespace PaginatorExtension;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

assertType('array', User::paginate()->all());
assertType('array', User::simplePaginate()->all());

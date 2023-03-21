<?php

declare(strict_types=1);

namespace Relation;

use App\Post;
use function PHPStan\Testing\assertType;

$relation = (new Post())->users();

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $relation->getEager());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $relation->get());
assertType('Illuminate\Database\Eloquent\Builder<App\User>', $relation->getQuery());
assertType('App\User', $relation->make());

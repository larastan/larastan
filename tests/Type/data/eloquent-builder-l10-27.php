<?php

namespace ModelRelations;

use App\User;
use function PHPStan\Testing\assertType;

function test(): void
{
    User::chunkByIdDesc(1000, fn ($collection) => assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection));
    User::orderedChunkById(1000, fn ($collection) => assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection));
}

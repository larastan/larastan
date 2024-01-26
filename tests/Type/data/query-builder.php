<?php

declare(strict_types=1);

namespace EloquentBuilder;

use Illuminate\Support\Facades\DB;

use function PHPStan\Testing\assertType;

function testPluckReturnType()
{
    $record = DB::table('user')->pluck('email', 'id');
    assertType('Illuminate\Support\Collection<(int|string), mixed>', $record);
}

assertType('Illuminate\Support\LazyCollection<int, mixed>', DB::table('user')->cursor());

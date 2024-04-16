<?php

namespace Model;

use App\Thread;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('string', Thread::valid()->toRawSql());
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid()->dumpRawSql());
}

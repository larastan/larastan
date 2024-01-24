<?php

namespace Model;

use App\Thread;

use function PHPStan\Testing\assertType;

function testToRawSql()
{
    assertType('string', Thread::valid()->toRawSql());
}

function testDumpRawSql()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid()->dumpRawSql());
}

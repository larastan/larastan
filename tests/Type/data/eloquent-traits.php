<?php

namespace EloquentTraitStubs;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;

use function PHPStan\Testing\assertType;

class Foo
{
    use HasTimestamps;
}

assertType('array<string>|bool', (new Foo())->timestamps);

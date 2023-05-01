<?php

declare(strict_types=1);

namespace Tests\Rules\data;

use Illuminate\Foundation\Events\Dispatchable;

class LaravelEventWithoutConstructor
{
    use Dispatchable;
}

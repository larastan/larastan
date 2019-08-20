<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Carbon\Carbon;

class CarbonExtension
{
    public function testCarbonMacro(): string
    {
        Carbon::macro('foo', function (): string {
            return 'foo';
        });

        return Carbon::foo().Carbon::now()->foo();
    }
}

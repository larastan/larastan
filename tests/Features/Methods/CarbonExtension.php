<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Carbon\Carbon;

Carbon::macro('foo', static function (): string {
    return 'foo';
});

class CarbonExtension
{
    public function testCarbonMacro(): string
    {
        return Carbon::foo().Carbon::now()->foo();
    }
}

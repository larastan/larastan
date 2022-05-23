<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Carbon\Carbon;

class CarbonExtension
{
    public function testCarbonMacroCalledStatically(): string
    {
        return Carbon::foo();
    }

    public function testCarbonMacroCalledDynamically(): string
    {
        return Carbon::now()->foo();
    }
}

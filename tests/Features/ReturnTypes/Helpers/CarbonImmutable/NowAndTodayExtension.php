<?php

namespace Features\ReturnTypes\Helpers\CarbonImmutable;

use Carbon\CarbonImmutable;

class NowAndTodayExtension
{
    public function testNowCanReturnImmutableCarbon(): CarbonImmutable
    {
        return now();
    }

    public function testTodayReturnImmutableCarbon(): CarbonImmutable
    {
        return today();
    }
}

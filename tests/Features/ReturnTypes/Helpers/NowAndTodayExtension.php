<?php

namespace Features\ReturnTypes\Helpers;

use Illuminate\Support\Carbon;

class NowAndTodayExtension
{
    public function testNowReturnsSupportCarbonByDefault(): Carbon
    {
        return now();
    }

    public function testTodayReturnsSupportCarbonByDefault(): Carbon
    {
        return today();
    }
}

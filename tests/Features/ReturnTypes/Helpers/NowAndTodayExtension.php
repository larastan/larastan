<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

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

    public function testNowCanReturnImmutableCarbon(): CarbonImmutable
    {
        Date::use(CarbonImmutable::class);

        return now();
    }

    public function testTodayReturnImmutableCarbon(): CarbonImmutable
    {
        Date::use(CarbonImmutable::class);

        return today();
    }
}

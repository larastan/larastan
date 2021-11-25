<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

Carbon::macro('foo', static function (): string {
    return 'foo';
});

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

    public function testCarbonMacroCallFromNowHelper(): string
    {
        return now()->foo();
    }

    public function testCarbonMacroCallFromDateFacade(): string
    {
        return Date::now()->foo();
    }

    public function testCarbonMacroCallFromSupportCarbonFacade(): string
    {
        return \Illuminate\Support\Carbon::now()->foo();
    }
}

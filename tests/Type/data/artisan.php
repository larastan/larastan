<?php

namespace Artisan;

use Illuminate\Support\Facades\Artisan;
use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function test(): void
{
    Artisan::command('inspire', function () {
        assertVariableCertainty(TrinaryLogic::createYes(), $this);
        assertType('Illuminate\Foundation\Console\ClosureCommand', $this);
    })->describe('Foo Bar');
}

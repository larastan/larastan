<?php
declare(strict_types=1);

namespace Rules\Data;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CorrectDeferrableProvider extends ServiceProvider implements DeferrableProvider
{
    public function provides(): array
    {
        return [
            'foo',
            'bar',
        ];
    }
}

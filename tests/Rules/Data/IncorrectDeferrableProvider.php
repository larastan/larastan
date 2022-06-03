<?php

declare(strict_types=1);

namespace Rules\Data;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class IncorrectDeferrableProvider extends ServiceProvider implements DeferrableProvider
{
}

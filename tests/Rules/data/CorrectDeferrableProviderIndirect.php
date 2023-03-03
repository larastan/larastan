<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Contracts\Support\DeferrableProvider;

class CorrectDeferrableProviderIndirect extends CorrectDeferrableProvider implements DeferrableProvider
{
}

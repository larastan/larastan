<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Contracts\Routing\UrlGenerator;

class UrlExtension
{
    public function testUrl(): string
    {
        return url('/path');
    }

    public function testUrlGenerator(): UrlGenerator
    {
        return url();
    }
}

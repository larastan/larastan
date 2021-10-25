<?php

declare(strict_types=1);

namespace Tests\Features\Laravel8\ReturnTypes;

use function PHPStan\Testing\assertType;

class Facades
{
    public function testHttpBaseUrl(): void
    {
        assertType(\Illuminate\Http\Client\PendingRequest::class, \Illuminate\Support\Facades\Http::baseUrl('foo'));
    }
}

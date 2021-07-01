<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

class Facades
{
    /** @phpstan-return ?\Illuminate\Http\Client\PendingRequest */
    public function testHttpBaseUrl()
    {
        if (class_exists(\Illuminate\Http\Client\PendingRequest::class)) {
            return \Illuminate\Support\Facades\Http::baseUrl('foo');
        }

        return null;
    }
}

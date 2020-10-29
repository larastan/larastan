<?php declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Facades
{
    public function testHttpBaseUrl(): PendingRequest
    {
        return Http::baseUrl('foo');
    }
}

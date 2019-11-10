<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class ResponseExtension
{
    public function testResponse(): Response
    {
        return response('foo');
    }

    public function testResponseFactory(): ResponseFactory
    {
        return response();
    }
}

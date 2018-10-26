<?php

declare(strict_types=1);

namespace App\TestFiles\ReturnTypes\Helpers;

use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Contracts\Routing\ResponseFactory;

class ResponseExtension
{
    public function testSimpleView(): Response
    {
        return response('test');
    }

    public function testFactory(): ResponseFactory
    {
        return response();
    }

    public function testFail(): View
    {
        return response('test');
    }
}

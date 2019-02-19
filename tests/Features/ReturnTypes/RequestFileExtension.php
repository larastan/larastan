<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Http\Request;

class RequestFileExtension
{
    public function testRequestFileStore()
    {
        $request = new Request();
        $request->file('image')->store('/path');
    }
}

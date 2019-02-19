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

    public function testRequestFileArrayStore()
    {
        $request = new Request();
        $request->file()[0]->store('/path');
    }
}

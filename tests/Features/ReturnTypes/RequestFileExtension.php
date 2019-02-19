<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class RequestFileExtension
{
    public function testRequestFileStore()
    {
        $request = new Request();
        $request->file('image', new UploadedFile('', ''))->store('/path');
    }

    public function testRequestFileArrayStore()
    {
        $request = new Request();
        $request->file()[0]->store('/path');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class RequestExtension
{
    /** @phpstan-return \Illuminate\Http\UploadedFile[] */
    public function testRequestFileWithNoArguments(Request $request): array
    {
        return $request->file();
    }

    public function testRequestFileWithJustKey(Request $request): ?UploadedFile
    {
        return $request->file('foo');
    }

    /** @phpstan-return DummyFile|UploadedFile */
    public function testRequestFileWithKeyAndDefaultValue(Request $request)
    {
        return $request->file('foo', new DummyFile());
    }
}

class DummyFile
{
}

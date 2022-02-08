<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class RequestFileExtension
{
    /** @phpstan-return UploadedFile[] */
    public function testRequestFileWithNoArguments(Request $request): array
    {
        return $request->file();
    }

    /** @phpstan-return UploadedFile[]|UploadedFile|null */
    public function testRequestFileWithJustKey(Request $request)
    {
        return $request->file('foo');
    }

    /** @phpstan-return DummyFile|UploadedFile|UploadedFile[] */
    public function testRequestFileWithKeyAndDefaultValue(Request $request)
    {
        return $request->file('foo', new DummyFile());
    }
}

class DummyFile
{
}

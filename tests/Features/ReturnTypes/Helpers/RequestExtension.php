<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Http\Request;

class RequestExtension
{
    public function testRequest(): Request
    {
        return \request();
    }

    /**
     * @return mixed
     */
    public function testMixed()
    {
        return \request('name');
    }

    /** @return array<mixed, mixed> */
    public function testArrayMixed(): array
    {
        return \request(['a', 'b']);
    }
}

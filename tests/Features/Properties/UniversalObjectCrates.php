<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use Illuminate\Http\Request;
use Illuminate\Support\Fluent;

class UniversalObjectCrates
{
    public function testRequest(): void
    {
        $request = resolve(Request::class);

        $foo = $request->foo;
    }

    public function testFluent(): void
    {
        $fluent = new Fluent(['foo' => 'bar']);

        $foo = $fluent->foo;
    }
}
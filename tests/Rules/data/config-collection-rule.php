<?php

namespace ConfigCollectionRule;

use Illuminate\Support\Facades\Config;

class Foo
{
    public function bar(): void
    {
        Config::collection('test.foo');
        Config::collection('test.bar');
        Config::collection('test.bar', 5);
    }
}

function namedArguments(): void
{
    Config::collection(default: null, key: 'test.foo');
    Config::collection(default: null, key: 'test.bar');
}

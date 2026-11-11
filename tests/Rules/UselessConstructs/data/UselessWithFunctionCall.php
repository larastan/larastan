<?php

declare(strict_types=1);

namespace Rules\UselessConstructs\data;

class UselessWithFunctionCall
{
    public function foo(): string
    {
        return with('foo');
    }

    public function bar(): string
    {
        return with('bar', null);
    }

    public function namedArguments(): string
    {
        return with(callback: null, value: 'baz');
    }
}

<?php

declare(strict_types=1);

namespace Rules\UselessConstructs\data;

class UselessValueFunctionCall
{
    public function foo(): string
    {
        return value('foo');
    }

    public function namedArguments(): string
    {
        return value(foo: 1, value: 'bar');
    }
}

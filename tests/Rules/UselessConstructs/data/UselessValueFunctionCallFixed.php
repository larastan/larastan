<?php

declare(strict_types=1);

namespace Rules\UselessConstructs\data;

class UselessValueFunctionCall
{
    public function foo(): string
    {
        return 'foo';
    }

    public function namedArguments(): string
    {
        return 'bar';
    }
}

<?php

declare(strict_types=1);

namespace Rules\UselessConstructs\data;

class UselessWithFunctionCall
{
    public function foo(): string
    {
        return 'foo';
    }

    public function bar(): string
    {
        return 'bar';
    }
}

<?php

declare(strict_types=1);

namespace Rules\Data\UselessConstructs;

class UselessValueFunctionCall
{
    public function foo(): string
    {
        return value('foo');
    }
}

<?php
declare(strict_types=1);

namespace Rules\Data\UselessConstructs;

use function with;

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
}

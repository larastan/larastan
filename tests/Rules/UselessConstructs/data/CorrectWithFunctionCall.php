<?php

declare(strict_types=1);

namespace Rules\UselessConstructs\data;

class CorrectWithFunctionCall
{
    public function foo(): string
    {
        return with('foo', static function (string $bar) {
            return strtoupper($bar);
        });
    }

    public function bar(): string
    {
        return with('foo', 'strtoupper');
    }

    public function namedArguments(): string
    {
        return with(callback: 'strtoupper', value: 'foo');
    }
}

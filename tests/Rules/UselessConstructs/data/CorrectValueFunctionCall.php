<?php

declare(strict_types=1);

namespace Rules\UselessConstructs\data;

class CorrectValueFunctionCall
{
    public function foo(): string
    {
        return value(static function (int $foo) {
            return 'foo';
        });
    }
}

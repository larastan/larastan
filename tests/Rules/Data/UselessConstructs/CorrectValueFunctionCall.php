<?php

declare(strict_types=1);

namespace Rules\Data\UselessConstructs;

class CorrectValueFunctionCall
{
    public function foo(): string
    {
        return value(static function (int $foo) {
            return 'foo';
        });
    }
}

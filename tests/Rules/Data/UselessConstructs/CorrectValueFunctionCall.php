<?php
declare(strict_types=1);

namespace Rules\Data\UselessConstructs;

use function with;

class CorrectValueFunctionCall
{
    public function foo(): string
    {
        return value(static function () {
            return 'foo';
        });
    }
}

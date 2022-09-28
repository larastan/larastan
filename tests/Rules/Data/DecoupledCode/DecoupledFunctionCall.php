<?php

declare(strict_types=1);

namespace Rules\Data\DecoupledCode;

class DecoupledFunctionCall
{
    public function foo(): void
    {
        class_basename('');
        e('');
        preg_replace_array('', [], '');
        str();
        class_uses_recursive('');
        collect();
        method_field('');
        now();
        optional();
        tap('');
        throw_if('');
        throw_unless('');
        today();
        trait_uses_recursive('');
        value('');
        with('');
        filled('');
        transform('', static function () {
        });
        retry(1, static function () {
        });
        $foo = blank('');
        $bar = blank('');
        dump('');
        dd();
    }
}

<?php

return [
    'foo' => 'Foo',
    'bar' => 'Bar',
    'baz' => 'Baz',

    'nested' => [
        'key' => 'value',
    ],

    'You\'re beautiful' => 'Du bist schön',

    'A.Key.With.Dots.' => 'A key with dots.',

    "with.new\nline.char" => 'Message with multi-line char,',

    'spanning
multiple.lines' => 'Message spanning multiple lines.',

    'with.a.back\\slash.single.quotes' => 'Message with a backslash.',
    "with.a.back\\slash.double.quotes" => 'Message with a backslash.',

    "with.\x61.hex.char" => 'Message with a hex char.',
];

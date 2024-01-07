<?php // lint >= 8.1

enum FooEnum: string
{
    case FOO = 'foo';
}

\Illuminate\Database\Eloquent\Builder::macro('macroWithEnumDefaultValue', function (string $arg = 'foobar', $b = FooEnum::FOO): string {
    return $arg;
});

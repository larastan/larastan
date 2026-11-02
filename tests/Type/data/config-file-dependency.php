<?php

namespace ConfigFileDependency;

function test(string $key): void
{
    config('auth.defaults');
    config()->get('test.foo');
    config('missing.foo');
    config($key);
}

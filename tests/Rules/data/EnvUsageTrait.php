<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use function Foo\Bar\env as scopedEnv;

trait EnvUsageTrait
{
    protected function callEnv(): void
    {
        // no report for namespaced calls
        \Foo\Bar\env('bar');
        scopedEnv('foo');

        env('foo');
        \env('bar');
    }
}

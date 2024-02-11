<?php

namespace Tests\Rules\Data;

use function Foo\Bar\env as scopedEnv;

env('foo');
\env('bar');

// no report for namespaced calls
\Foo\Bar\env('bar');
scopedEnv('foo');

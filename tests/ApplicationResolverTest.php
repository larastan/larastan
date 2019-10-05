<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use NunoMaduro\Larastan\ApplicationResolver;
use NunoMaduro\Larastan\LarastanServiceProvider;

class ApplicationResolverTest extends TestCase
{
    public function testDefaultResolve()
    {
        $result = ApplicationResolver::resolve();
        $resolve = $result->getProviders(LarastanServiceProvider::class);
        $this->assertTrue(
            is_array($resolve) && 0 < count($resolve),
            'LarastanServiceProvider not loaded by ApplicationResolver'
        );
    }
}

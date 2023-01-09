<?php

namespace AppMake;

use Illuminate\Support\Facades\App;
use NunoMaduro\Larastan\ApplicationResolver;
use function PHPStan\Testing\assertType;

class AppMakeDynamicReturnTypeExtension
{
    public function testResolvesAppMakeStaticCall(): void
    {
        assertType(ApplicationResolver::class, App::make(ApplicationResolver::class));
    }

    public function testResolvesAppMakeWithStaticCall(): void
    {
        assertType(ApplicationResolver::class, App::makeWith(ApplicationResolver::class));
    }
}

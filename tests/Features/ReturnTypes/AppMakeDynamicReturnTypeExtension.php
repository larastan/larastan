<?php

declare(strict_types=1);

namespace Features\ReturnTypes;

use Illuminate\Support\Facades\App;
use NunoMaduro\Larastan\ApplicationResolver;

class AppMakeDynamicReturnTypeExtension
{
    public function testResolvesAppMakeStaticCall(): ApplicationResolver
    {
        return App::make(ApplicationResolver::class);
    }

    public function testResolvesAppMakeWithStaticCall(): ApplicationResolver
    {
        return App::makeWith(ApplicationResolver::class);
    }
}

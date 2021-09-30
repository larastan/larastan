<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\App;
use NunoMaduro\Larastan\ApplicationResolver;

class AppExtension
{
    public function testAppObjectType(): ApplicationResolver
    {
        return App::make(ApplicationResolver::class);
    }

    /**
     * @return mixed
     */
    public function testAppMixedType()
    {
        return App::make('sentry');
    }

    public function testAppAuthString(): AuthManager
    {
        return App::make('auth');
    }

    /**
     * @return null
     */
    public function testAppNull()
    {
        app()->bind('null', function () {
        });

        return App::make('null');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use NunoMaduro\Larastan\ApplicationResolver;

class AppExtension
{
    public function testObjectType(): ApplicationResolver
    {
        return app(ApplicationResolver::class);
    }

    public function testMixedTypeNoArgument(): Application
    {
        return app();
    }

    /**
     * @return mixed
     */
    public function testMixedTypeFor()
    {
        return app('sentry');
    }

    public function testAuthString(): AuthManager
    {
        return app('auth');
    }
}

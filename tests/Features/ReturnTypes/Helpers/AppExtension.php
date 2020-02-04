<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use NunoMaduro\Larastan\ApplicationResolver;

class AppExtension
{
    public function testAppNoArgument(): Application
    {
        return app();
    }

    public function testAppObjectType(): ApplicationResolver
    {
        return app(ApplicationResolver::class);
    }

    /**
     * @return mixed
     */
    public function testAppMixedType()
    {
        return app('sentry');
    }

    public function testAppAuthString(): AuthManager
    {
        return app('auth');
    }

    /**
     * @return null
     */
    public function testAppNull()
    {
        app()->bind('null', function () {
        });

        return app('null');
    }

    public function testResolveObjectType(): ApplicationResolver
    {
        return resolve(ApplicationResolver::class);
    }

    /**
     * @return mixed
     */
    public function testResolveMixedType()
    {
        return resolve('sentry');
    }

    public function testResolveAuthString(): AuthManager
    {
        return resolve('auth');
    }

    /**
     * @return null
     */
    public function testResolveNull()
    {
        app()->bind('null', function () {
        });

        return resolve('null');
    }
}

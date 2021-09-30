<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Auth\AuthManager;
use Illuminate\Container\Container;
use NunoMaduro\Larastan\ApplicationResolver;

class ContainerExtension
{
    public function testContainerMakeObjectType(): ApplicationResolver
    {
        return Container::getInstance()->make(ApplicationResolver::class);
    }

    /**
     * @return mixed
     */
    public function testContainerMakeMixedType()
    {
        return Container::getInstance()->make('sentry');
    }

    public function testContainerMakeAuthString(): AuthManager
    {
        return Container::getInstance()->make('auth');
    }

    /**
     * @return null
     */
    public function testContainerMakeNull()
    {
        app()->bind('null', function () {
        });

        return Container::getInstance()->make('null');
    }

    public function testAppMakeObjectType(): ApplicationResolver
    {
        return app()->make(ApplicationResolver::class);
    }

    /**
     * @return mixed
     */
    public function testAppMakeMixedType()
    {
        return app()->make('sentry');
    }

    public function testAppMakeAuthString(): AuthManager
    {
        return app()->make('auth');
    }

    /**
     * @return null
     */
    public function testAppMakeNull()
    {
        app()->bind('null', function () {
        });

        return app()->make('null');
    }
}

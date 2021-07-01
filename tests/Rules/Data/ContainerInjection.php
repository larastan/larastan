<?php

declare(strict_types=1);

use Illuminate\Contracts\Foundation\Application;

class FooServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Service::class, function () {
            return new Service($this->app);
        });

        $this->app->bind(Service::class, function () {
            return new Service($this->app);
        });

        // This is fine
        $this->app->bind(Service::class, function ($app) {
            return new Service($app);
        });

        $this->app->singleton(Service::class, function ($app) {
            return new Service($app);
        });

        $this->app->singleton(Service::class, function ($app) {
            return new Service($app['request']);
        });

        $this->app->singleton(Service::class, function ($app) {
            return new Service($app['config']);
        });

        // This is fine
        $this->app->singleton(Service::class, function ($app) {
            return new Service($app['session']);
        });
    }
}

function foo(Application $app): void
{
    $app->singleton(Service::class, function ($app) {
        return new Service($app);
    });
}

(new \Illuminate\Foundation\Application())->singleton(Service::class, function ($app) {
    return new Service($app);
});

class Service
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }
}

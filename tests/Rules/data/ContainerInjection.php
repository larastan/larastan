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

// The same bindings written as arrow functions. An arrow function is not a
// subclass of Closure in PHP-Parser, so these used to be skipped outright.
class BarServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Service::class, fn () => new Service($this->app));

        // Reported for the same reason the closure form on line 15 is: with no
        // parameter the rule falls through to the $this->app check.
        $this->app->bind(Service::class, fn () => new Service($this->app));

        // This is fine
        $this->app->bind(Service::class, fn ($app) => new Service($app));

        $this->app->singleton(Service::class, fn ($app) => new Service($app));

        $this->app->singleton(Service::class, fn ($app) => new Service($app['request']));

        // This is fine
        $this->app->singleton(Service::class, fn ($app) => new Service($app['session']));
    }
}

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

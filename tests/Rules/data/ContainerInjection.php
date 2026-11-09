<?php

declare(strict_types=1);

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

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

class MultiArgService
{
    public function __construct(string $name, $dependency)
    {
    }
}

class AdvancedInjectionServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Non-first constructor argument: $app
        $this->app->singleton(MultiArgService::class, function ($app) {
            return new MultiArgService('foo', $app);
        });

        // Non-first constructor argument: $app['request']
        $this->app->singleton(MultiArgService::class, function ($app) {
            return new MultiArgService('foo', $app['request']);
        });

        // $app->make('request')
        $this->app->singleton(Service::class, function ($app) {
            return new Service($app->make('request'));
        });

        // $app->get('config')
        $this->app->singleton(Service::class, function ($app) {
            return new Service($app->get('config'));
        });

        // $app->makeWith(Request::class)
        $this->app->singleton(Service::class, function ($app) {
            return new Service($app->makeWith(\Illuminate\Http\Request::class, []));
        });

        // $app[Request::class]
        $this->app->singleton(Service::class, function ($app) {
            return new Service($app[\Illuminate\Http\Request::class]);
        });

        // $app->make(\Illuminate\Contracts\Config\Repository::class)
        $this->app->singleton(Service::class, function ($app) {
            return new Service($app->make(\Illuminate\Contracts\Config\Repository::class));
        });

        // Non-forbidden make is fine
        $this->app->singleton(Service::class, function ($app) {
            return new Service($app->make('cache'));
        });
    }
}

// The cases from issue #2597 as reported: arrow functions and an imported
// Request class.
class ArrowFunctionInjectionServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->singleton(MultiArgService::class, fn ($app) => new MultiArgService('foo', $app['request']));
        $this->app->singleton(Service::class, fn ($app) => new Service($app->make('request')));
        $this->app->singleton(Service::class, fn ($app) => new Service($app->make(Request::class)));
        $this->app->singleton(Service::class, fn ($app) => new Service($app[Request::class]));
        $this->app->singleton(MultiArgService::class, fn ($app) => new MultiArgService(name: 'foo', dependency: $app['config']));
        $this->app->singleton(Service::class, fn ($app) => new Service($app->make(\Illuminate\Config\Repository::class)));

        // This is fine: a value read from the config, not the repository itself
        $this->app->singleton(Service::class, fn ($app) => new Service($app['config']['app.name']));
        $this->app->singleton(Service::class, fn ($app) => new Service($app->make('config')->get('app.name')));
    }
}

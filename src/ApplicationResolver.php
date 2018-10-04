<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan;

use function in_array;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Foundation\Application;
use Orchestra\Testbench\Concerns\CreatesApplication;

/**
 * @internal
 */
final class ApplicationResolver
{
    use CreatesApplication;

    /**
     * Creates an application and registers service providers found.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public static function resolve(): Application
    {
        $app = (new self)->createApplication();

        $serviceProviders = array_values(array_filter(self::getProjectClasses(), function (string $class) {
            return self::isServiceProvider($class);
        }));

        foreach ($serviceProviders as $serviceProvider) {
            $app->register($serviceProvider);
        }

        return $app;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentSetUp($app): void
    {
        // ..
    }

    /**
     * @param  string $class
     *
     * @return bool
     */
    private static function isServiceProvider($class): bool
    {
        return in_array(\Illuminate\Support\ServiceProvider::class, class_parents($class), true);
    }

    /**
     * @return array
     */
    private static function getProjectClasses(): array
    {
        $files = Finder::create()->files()->name('*.php')->in(getcwd().DIRECTORY_SEPARATOR.'src');

        foreach ($files->files() as $file) {
            require_once $file;
        }

        return get_declared_classes();
    }
}

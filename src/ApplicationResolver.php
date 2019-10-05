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

use Composer\Autoload\ClassLoader;
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

        $composerFile = getcwd().DIRECTORY_SEPARATOR.'composer.json';

        if (file_exists($composerFile)) {
            $namespace = (string) key(json_decode((string) file_get_contents($composerFile), true)['autoload']['psr-4']);

            $serviceProviders = array_values(array_filter(self::getProjectClasses($namespace), function (string $class) use (
                $namespace
            ) {
                return substr($class, 0, strlen($namespace)) === $namespace && self::isServiceProvider($class);
            }));

            foreach ($serviceProviders as $serviceProvider) {
                $app->register($serviceProvider);
            }
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
     * @param string $namespace
     *
     * @return array
     */
    private static function getProjectClasses(string $namespace): array
    {
        $files = Finder::create()->files()->name('*.php')->in(self::getProjectSearchDirs($namespace));

        foreach ($files->files() as $file) {
            try {
                require_once $file;
            } catch (\Throwable $e) {
                // ..
            }
        }

        return get_declared_classes();
    }

    /**
     * @param string $namespace
     *
     * @return string[]
     */
    private static function getProjectSearchDirs(string $namespace): array
    {
        $file = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer'
                . DIRECTORY_SEPARATOR . 'autoload_psr4.php';
        $raw = require_once $file;
        return $raw[$namespace];
    }
}

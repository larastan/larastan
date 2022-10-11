<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Config;

/**
 * @internal
 */
final class ApplicationResolver
{
    /**
     * Create symlink on vendor path.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function createSymlinkToVendorPath($app): void
    {
        $workingVendorPath = TESTBENCH_WORKING_PATH.'/vendor';

        $filesystem = new Filesystem();

        $laravelVendorPath = $app->basePath('vendor');

        if (
            "{$laravelVendorPath}/autoload.php" !== "{$workingVendorPath}/autoload.php"
        ) {
            $filesystem->delete($laravelVendorPath);
            $filesystem->link($workingVendorPath, $laravelVendorPath);
        }

        $app->flush();
    }

    /**
     * Creates an application and registers service providers found.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     *
     * @throws \ReflectionException
     */
    public static function resolve(): Application
    {
        if (! defined('TESTBENCH_WORKING_PATH')) {
            define('TESTBENCH_WORKING_PATH', $workingPath = getcwd());
        }

        $resolvingCallback = function ($app) {
            $packageManifest = $app->make(PackageManifest::class);

            $packageManifest->build();
        };

        if (class_exists(Config::class)) {
            $config = Config::loadFromYaml($workingPath);

            static::createSymlinkToVendorPath(Testbench::create(basePath: $config['laravel']));

            return Testbench::create(
                basePath: $config['laravel'],
                resolvingCallback: $resolvingCallback,
                options: ['enables_package_discoveries' => true, 'extra' => $config->getExtraAttributes()]
            );
        }

        static::createSymlinkToVendorPath(Testbench::create(basePath: Testbench::applicationBasePath()));

        return Testbench::create(
            resolvingCallback: $resolvingCallback,
            options: ['enables_package_discoveries' => true]
        );
    }
}

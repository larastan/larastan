<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use Illuminate\Contracts\Foundation\Application;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Config;
use Illuminate\Foundation\PackageManifest as IlluminatePackageManifest;

/**
 * @internal
 */
final class ApplicationResolver
{
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
            $packageManifest = $app->make(IlluminatePackageManifest::class);

            if (! file_exists($packageManifest->manifestPath)) {
                $packageManifest->build();
            }
        };

        if (class_exists(Config::class)) {
            $config = Config::loadFromYaml($workingPath);

            return Testbench::create(
                basePath: $config['laravel'],
                resolvingCallback: $resolvingCallback,
                options: ['enables_package_discoveries' => true, 'extra' => $config->getExtraAttributes()]
            );
        }

        return Testbench::create(
            resolvingCallback: $resolvingCallback,
            options: ['enables_package_discoveries' => true]
        );
    }
}

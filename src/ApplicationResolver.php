<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Config;

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
        $resolvingCallback = function ($app) {
            $packageManifest = $app->make(PackageManifest::class);

            if (! file_exists($packageManifest->manifestPath)) {
                $packageManifest->build();
            }
        };

        if (class_exists(Config::class)) {
            $config = Config::loadFromYaml(getcwd());

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

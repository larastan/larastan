<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use NunoMaduro\Larastan\Internal\ComposerHelper;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Config;

use function defined;
use function file_exists;
use function getcwd;

/**
 * @internal
 */
final class ApplicationResolver
{
    /**
     * Creates an application and registers service providers found.
     *
     *
     * @throws \ReflectionException
     */
    public static function resolve(): Application
    {
        /** @var non-empty-string $workingPath */
        $workingPath = getcwd();
        if (! defined('TESTBENCH_WORKING_PATH')) {
            define('TESTBENCH_WORKING_PATH', $workingPath);
        }

        if ($composerConfig = ComposerHelper::getComposerConfig($workingPath)) {
            $vendorDir = ComposerHelper::getVendorDirFromComposerConfig($workingPath, $composerConfig);
        } else {
            $vendorDir = $workingPath.'/vendor';
        }

        $resolvingCallback = function ($app) {
            $packageManifest = $app->make(PackageManifest::class);

            if (! file_exists($packageManifest->manifestPath)) {
                $packageManifest->build();
            }
        };

        $config = Config::loadFromYaml($workingPath);

        Testbench::createVendorSymlink($config['laravel'], $vendorDir);

        return Testbench::createFromConfig(
            $config,
            $resolvingCallback,
            ['enables_package_discoveries' => true]
        );
    }
}

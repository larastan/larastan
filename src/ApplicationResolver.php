<?php

declare(strict_types=1);

namespace Larastan\Larastan;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use Larastan\Larastan\Internal\ComposerHelper;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Bootstrap\CreateVendorSymlink;
use Orchestra\Testbench\Foundation\Config;
use ReflectionException;

use function define;
use function defined;
use function file_exists;
use function getcwd;

/** @internal */
final class ApplicationResolver
{
    /**
     * Create symlink on vendor path.
     */
    public static function createSymlinkToVendorPath(Application $app, string $vendorDir): void
    {
        (new CreateVendorSymlink($vendorDir))->bootstrap($app);
    }

    /**
     * Creates an application and registers service providers found.
     *
     * @throws ReflectionException
     */
    public static function resolve(): Application
    {
        /** @var non-empty-string $workingPath */
        $workingPath = getcwd();

        if (! defined('TESTBENCH_WORKING_PATH')) {
            define('TESTBENCH_WORKING_PATH', $workingPath);
        }

        $composerConfig = ComposerHelper::getComposerConfig($workingPath);

        if ($composerConfig) {
            $vendorDir = ComposerHelper::getVendorDirFromComposerConfig($workingPath, $composerConfig);
        } else {
            $vendorDir = $workingPath . '/vendor';
        }

        $resolvingCallback = static function ($app): void {
            $packageManifest = $app->make(PackageManifest::class);

            if ($packageManifest->manifestPath === null || file_exists($packageManifest->manifestPath)) {
                return;
            }

            $packageManifest->build();
        };

        $config = Config::loadFromYaml($workingPath);

        self::createSymlinkToVendorPath(Testbench::create($config['laravel'], null, ['extra' => ['dont-discover' => ['*']]]), $vendorDir);

        return Testbench::create(
            $config['laravel'],
            $resolvingCallback,
            ['enabled_package_discoveries' => true, 'extra' => $config->getExtraAttributes()],
        );
    }
}

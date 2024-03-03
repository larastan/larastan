<?php

declare(strict_types=1);

namespace Larastan\Larastan;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Larastan\Larastan\Internal\ComposerHelper;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Bootstrap\CreateVendorSymlink;
use Orchestra\Testbench\Foundation\Config;
use ReflectionException;

use function class_exists;
use function define;
use function defined;
use function file_exists;
use function getcwd;
use function sprintf;

/** @internal */
final class ApplicationResolver
{
    /**
     * Create symlink on vendor path.
     */
    public static function createSymlinkToVendorPath(Application $app, string $vendorDir): void
    {
        if (class_exists(CreateVendorSymlink::class)) {
            (new CreateVendorSymlink($vendorDir))->bootstrap($app);

            return;
        }

        $filesystem = new Filesystem();

        $laravelVendorPath = $app->basePath('vendor');

        if (
            sprintf('%s/autoload.php', $laravelVendorPath) !== sprintf('%s/autoload.php', $vendorDir)
        ) {
            if ($filesystem->exists($app->bootstrapPath('cache/packages.php'))) {
                $filesystem->delete($app->bootstrapPath('cache/packages.php'));
            }

            $filesystem->delete($laravelVendorPath);
            $filesystem->link($vendorDir, $laravelVendorPath);
        }

        $app->flush();
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

            if (file_exists($packageManifest->manifestPath)) {
                return;
            }

            $packageManifest->build();
        };

        if (class_exists(Config::class)) {
            $config = Config::loadFromYaml($workingPath);

            self::createSymlinkToVendorPath(Testbench::create($config['laravel'], null, ['extra' => ['dont-discover' => ['*']]]), $vendorDir);

            return Testbench::create(
                $config['laravel'],
                $resolvingCallback,
                ['enables_package_discoveries' => true, 'extra' => $config->getExtraAttributes()],
            );
        }

        self::createSymlinkToVendorPath(Testbench::create(Testbench::applicationBasePath(), null, ['extra' => ['dont-discover' => ['*']]]), $vendorDir);

        return Testbench::create(
            null,
            $resolvingCallback,
            ['enables_package_discoveries' => true],
        );
    }
}

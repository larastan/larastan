<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use Composer\Autoload\ClassMapGenerator;
use const DIRECTORY_SEPARATOR;
use Illuminate\Contracts\Foundation\Application;
use function in_array;
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
     * @throws \ReflectionException
     */
    public static function resolve(): Application
    {
        $app = (new self)->createApplication();

        $composerFile = getcwd().DIRECTORY_SEPARATOR.'composer.json';

        if (file_exists($composerFile)) {
            $namespace = (string) key(json_decode((string) file_get_contents($composerFile), true)['autoload']['psr-4']);
            $serviceProviders = array_values(array_filter(self::getProjectClasses($namespace, dirname($composerFile)), function (string $class) use (
                $namespace
            ) {
                /** @var class-string $class */
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
    protected function getEnvironmentSetUp($app)
    {
        // ..
    }

    /**
     * @phpstan-param class-string $class
     *
     * @return bool
     * @throws \ReflectionException
     */
    private static function isServiceProvider(string $class): bool
    {
        return in_array(\Illuminate\Support\ServiceProvider::class, class_parents($class), true)
            && ! (new \ReflectionClass($class))->isAbstract();
    }

    /**
     * @param string $namespace
     *
     * @return string[]
     * @throws \ReflectionException
     */
    private static function getProjectClasses(string $namespace, string $rootDir): array
    {
        $projectDirs = self::getProjectSearchDirs($namespace, $rootDir);
        /** @var string[] $maps */
        $maps = [];
        // Use composer's ClassMapGenerator to pull the class list out of each project search directory
        foreach ($projectDirs as $dir) {
            $maps = array_merge($maps, ClassMapGenerator::createMap($dir));
        }

        // now class list of maps are assembled, use class_exists calls to explicitly autoload them,
        // while not running them
        foreach ($maps as $class => $file) {
            class_exists($class, true);
        }

        return get_declared_classes();
    }

    /**
     * @param string $namespace
     * @param string $rootDir
     *
     * @return string[]
     * @throws \ReflectionException
     */
    private static function getProjectSearchDirs(string $namespace, string $rootDir): array
    {
        $composerDir = $rootDir.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'composer';

        $file = $composerDir.DIRECTORY_SEPARATOR.'autoload_psr4.php';
        $raw = include $file;

        return $raw[$namespace];
    }
}

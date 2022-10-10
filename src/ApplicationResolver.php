<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 */
final class ApplicationResolver
{
    use CreatesApplication;

    public static Application $bootedApp;

    /** @var bool */
    protected $enablesPackageDiscoveries = true;

    /**
     * Creates an application and registers service providers found.
     *
     * @return Application
     */
    public static function resolve(): Application
    {
        if (self::$bootedApp) {
            return self::$bootedApp;
        }

        $tempApp = (new self)->createApplication();
        try {
            exec($tempApp->get(PackageManifest::class)->vendorPath.'/bin/testbench package:discover');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            // do nothing
        }

        return self::$bootedApp = (new self)->createApplication();
    }
}

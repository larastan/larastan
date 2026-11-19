<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Testing\TestComponent;
use Illuminate\Testing\TestResponse;
use Larastan\Larastan\Support\MacroHelper;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use ReflectionException;

#[AutowiredService]
class MacroMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    private const FORWARDS_CALLS_AFTER_MACROS = [
        CacheRepository::class,
        DatabaseManager::class,
        FilesystemAdapter::class,
        HttpClientFactory::class,
        HttpClientResponse::class,
        JsonResource::class,
        ProcessFactory::class,
        RedisConnection::class,
        Router::class,
        RouteRegistrar::class,
        Schedule::class,
        TestComponent::class,
        TestResponse::class,
    ];

    /** @var array<string, MethodReflection> */
    private array $methods = [];

    private string|null $macroableFileName = null;

    public function __construct(private MacroHelper $macroHelper, private ReflectionProvider $reflectionProvider)
    {
    }

    /**
     * @throws ReflectionException
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (
            $classReflection->hasNativeMethod('__call')
            && ! $this->usesMacroableCall($classReflection)
            && ! $this->forwardsCallsAfterMacros($classReflection)
        ) {
            return false;
        }

        $found = $this->macroHelper->findMacro($classReflection, $methodName);

        if ($found) {
            $this->methods[$classReflection->getName() . '-' . $methodName] = $found;

            return true;
        }

        return false;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName,
    ): MethodReflection {
        return $this->methods[$classReflection->getName() . '-' . $methodName];
    }

    private function usesMacroableCall(ClassReflection $classReflection): bool
    {
        $this->macroableFileName ??= $this->reflectionProvider->getClass(Macroable::class)->getFileName();

        return $classReflection->getNativeReflection()->getMethod('__call')->getFileName() === $this->macroableFileName;
    }

    private function forwardsCallsAfterMacros(ClassReflection $classReflection): bool
    {
        foreach (self::FORWARDS_CALLS_AFTER_MACROS as $className) {
            if ($classReflection->is($className)) {
                return true;
            }
        }

        return false;
    }
}

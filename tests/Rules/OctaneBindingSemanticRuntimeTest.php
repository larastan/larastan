<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use OctaneSemanticRoots\CaptiveMappedConcrete;
use OctaneSemanticRoots\RootFactory;
use OctaneSemanticRoots\RootInstance;
use OctaneSemanticRoots\RootMapped;
use OctaneSemanticRoots\RootMappedCaptive;
use OctaneSemanticRoots\RootSelf;
use OctaneSemanticRoots\RootToScoped;
use OctaneSemanticRoots\SafeExistingInstance;
use OctaneSemanticRoots\SafeFactoryConcrete;
use OctaneSemanticRoots\SafeMappedConcrete;
use OctaneSemanticRoots\ScopedMappedConcrete;
use OctaneSemanticShared\NamedShared;
use OctaneSemanticShared\PositionalShared;
use OctaneSemanticShared\SharedViaBindIf;
use OctaneSemanticShared\TransientDefault;
use OctaneSemanticShared\TransientExplicit;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function class_exists;
use function OctaneSemanticRoots\register as registerRoots;
use function OctaneSemanticShared\register as registerShared;

final class OctaneBindingSemanticRuntimeTest extends TestCase
{
    public function testNativeSingletonAttributeUsesExplicitConcreteNotAbstractConstructor(): void
    {
        if (! class_exists(Singleton::class)) {
            self::markTestSkipped('Native Singleton attribute requires Laravel 12+.');
        }

        require_once __DIR__ . '/data/OctaneSemanticRoots.php';

        $previous = Container::getInstance();

        try {
            $app          = new Application();
            $firstRequest = Request::create('/first');
            $app->instance('request', $firstRequest);
            registerRoots($app);

            $factory  = $app->make(RootFactory::class);
            $mapped   = $app->make(RootMapped::class);
            $actual   = $app->make(RootMappedCaptive::class);
            $scoped   = $app->make(RootToScoped::class);
            $instance = $app->make(RootInstance::class);
            $rootSelf = $app->make(RootSelf::class);

            self::assertInstanceOf(SafeFactoryConcrete::class, $factory);
            self::assertInstanceOf(SafeMappedConcrete::class, $mapped);
            self::assertInstanceOf(CaptiveMappedConcrete::class, $actual);
            self::assertInstanceOf(ScopedMappedConcrete::class, $scoped);
            self::assertInstanceOf(SafeExistingInstance::class, $instance);
            self::assertFalse((new ReflectionProperty(RootFactory::class, 'request'))->isInitialized($factory));
            self::assertFalse((new ReflectionProperty(RootMapped::class, 'request'))->isInitialized($mapped));
            self::assertSame($firstRequest, $actual->request);
            self::assertSame($firstRequest, $rootSelf->request);

            $app->instance('request', Request::create('/second'));
            $app->forgetScopedInstances();

            self::assertSame($factory, $app->make(RootFactory::class));
            self::assertSame($mapped, $app->make(RootMapped::class));
            self::assertSame($actual, $app->make(RootMappedCaptive::class));
            self::assertSame($firstRequest, $app->make(RootMappedCaptive::class)->request);
            self::assertSame($scoped, $app->make(RootToScoped::class));
            self::assertNotSame($scoped, $app->make(ScopedMappedConcrete::class));
            self::assertSame($instance, $app->make(RootInstance::class));
            self::assertSame($rootSelf, $app->make(RootSelf::class));
        } finally {
            Container::setInstance($previous);
        }
    }

    public function testSharedBindFlagActuallyCachesRequestAcrossRequests(): void
    {
        require_once __DIR__ . '/data/OctaneSemanticShared.php';

        $previous = Container::getInstance();

        try {
            $app          = new Application();
            $firstRequest = Request::create('/first');
            $app->instance('request', $firstRequest);
            registerShared($app);

            $named       = $app->make(NamedShared::class);
            $positional  = $app->make(PositionalShared::class);
            $conditional = $app->make(SharedViaBindIf::class);
            $transient   = $app->make(TransientDefault::class);
            $explicit    = $app->make(TransientExplicit::class);

            $app->instance('request', Request::create('/second'));
            $app->forgetScopedInstances();

            self::assertSame($named, $app->make(NamedShared::class));
            self::assertSame($positional, $app->make(PositionalShared::class));
            self::assertSame($conditional, $app->make(SharedViaBindIf::class));
            self::assertSame($firstRequest, $app->make(NamedShared::class)->request);
            self::assertNotSame($transient, $app->make(TransientDefault::class));
            self::assertNotSame($explicit, $app->make(TransientExplicit::class));
        } finally {
            Container::setInstance($previous);
        }
    }
}

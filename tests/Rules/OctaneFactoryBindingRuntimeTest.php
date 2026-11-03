<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use ScopeFactoryBinding\ClosureBoundService;
use ScopeFactoryBinding\FactoryBoundService;
use ScopeFactoryBinding\FactoryScopedContext;
use ScopeFactoryBinding\SafeClosureResult;
use ScopeFactoryBinding\SafeFactoryResult;
use ScopeFactoryBinding\SingletonWithClosure;
use ScopeFactoryBinding\SingletonWithFactory;
use ScopeFactoryBinding\SingletonWithScopedFactory;

use function ScopeFactoryBinding\register;

final class OctaneFactoryBindingRuntimeTest extends TestCase
{
    public function testAnUnrelatedFactoryConstructorIsNeverUsed(): void
    {
        require_once __DIR__ . '/data/CaptiveScopeFactoryBinding.php';

        $container = new Container();

        register($container);

        $arrowOwner   = $container->make(SingletonWithFactory::class);
        $closureOwner = $container->make(SingletonWithClosure::class);

        self::assertInstanceOf(SafeFactoryResult::class, $arrowOwner->service);
        self::assertInstanceOf(SafeClosureResult::class, $closureOwner->service);
        self::assertFalse((new ReflectionProperty(FactoryBoundService::class, 'request'))->isInitialized($arrowOwner->service));
        self::assertFalse((new ReflectionProperty(ClosureBoundService::class, 'request'))->isInitialized($closureOwner->service));

        $container->forgetScopedInstances();

        self::assertSame($arrowOwner, $container->make(SingletonWithFactory::class));
        self::assertSame($closureOwner, $container->make(SingletonWithClosure::class));
    }

    public function testScopedFactoryIsStillScopedEvenWithUnknownConcrete(): void
    {
        require_once __DIR__ . '/data/CaptiveScopeFactoryBinding.php';

        $container = new Container();

        register($container);

        $owner   = $container->make(SingletonWithScopedFactory::class);
        $context = $owner->context;

        self::assertSame($context, $container->make(FactoryScopedContext::class));

        $container->forgetScopedInstances();

        self::assertSame($owner, $container->make(SingletonWithScopedFactory::class));
        self::assertSame($context, $owner->context);
        self::assertNotSame($context, $container->make(FactoryScopedContext::class));
    }
}

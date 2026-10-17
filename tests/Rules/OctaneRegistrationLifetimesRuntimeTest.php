<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use ScopeAcrossFiles\AttributeOwner;
use ScopeAcrossFiles\ContextOwner;
use ScopeAcrossFiles\DelegateContract;
use ScopeAcrossFiles\DelegateOwner;
use ScopeAcrossFiles\OverriddenScopedContext;
use ScopeAcrossFiles\SafeOwner;
use ScopeAcrossFiles\ScopedContext;
use ScopeAcrossFiles\ScopedDelegate;

use function class_exists;

final class OctaneRegistrationLifetimesRuntimeTest extends TestCase
{
    public function testPlainScopedDependencyIsRetainedBySingleton(): void
    {
        $container = new Container();
        $container->scoped(ScopedContext::class);
        $container->singleton(ContextOwner::class);

        $owner        = $container->make(ContextOwner::class);
        $firstContext = $owner->context;

        $this->assertSame($firstContext, $container->make(ScopedContext::class));

        $container->forgetScopedInstances();

        $this->assertSame($owner, $container->make(ContextOwner::class));
        $this->assertNotSame($firstContext, $container->make(ScopedContext::class));
        $this->assertSame($firstContext, $owner->context);
    }

    public function testConcreteScopedAttributeSurvivesTransientInterfaceBinding(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Scoped')) {
            self::markTestSkipped('Native Scoped attribute is unavailable.');
        }

        $container = new Container();
        $container->bind(DelegateContract::class, ScopedDelegate::class);
        $container->singleton(DelegateOwner::class);

        $owner = $container->make(DelegateOwner::class);

        $this->assertSame($owner->context, $container->make(DelegateContract::class));

        $container->forgetScopedInstances();

        $this->assertNotSame($owner->context, $container->make(DelegateContract::class));
        $this->assertSame($owner, $container->make(DelegateOwner::class));
    }

    public function testExplicitBindingsDetermineActualLifetimeOverAttributes(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Scoped') || ! class_exists('Illuminate\\Container\\Attributes\\Singleton')) {
            self::markTestSkipped('Native lifetime attributes are unavailable.');
        }

        $container = new Container();
        $container->singleton(OverriddenScopedContext::class);
        $container->singleton(SafeOwner::class);
        $container->scoped(AttributeOwner::class);

        $context        = $container->make(OverriddenScopedContext::class);
        $owner          = $container->make(SafeOwner::class);
        $attributeOwner = $container->make(AttributeOwner::class);

        $container->forgetScopedInstances();

        $this->assertSame($context, $container->make(OverriddenScopedContext::class));
        $this->assertSame($context, $owner->context);
        $this->assertNotSame($attributeOwner, $container->make(AttributeOwner::class));
    }
}

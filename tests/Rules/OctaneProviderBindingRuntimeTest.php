<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use OctaneProviderBindingAudit\HiddenConstructorRoot;
use OctaneProviderBindingAudit\ProviderListOwner;
use OctaneProviderBindingAudit\ProviderOwner;
use OctaneProviderBindingAudit\RegistrationProvider;
use OctaneProviderBindingAudit\SafeRootImplementation;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function class_exists;

final class OctaneProviderBindingRuntimeTest extends TestCase
{
    public function testPublicProviderBindingMapsMatchRealLaravelBehavior(): void
    {
        require_once __DIR__ . '/data/OctaneProviderBindingAudit.php';

        $previous = Container::getInstance();

        try {
            $app          = new Application();
            $firstRequest = Request::create('/first');
            $app->instance('request', $firstRequest);
            $app->register(RegistrationProvider::class);

            $owner   = $app->make(ProviderOwner::class);
            $through = $app->make(ProviderListOwner::class);
            $mapped  = $app->make(HiddenConstructorRoot::class);

            self::assertSame($firstRequest, $owner->request);
            self::assertSame($firstRequest, $through->carrier->request);
            self::assertInstanceOf(SafeRootImplementation::class, $mapped);
            self::assertFalse((new ReflectionProperty(HiddenConstructorRoot::class, 'request'))->isInitialized($mapped));

            $app->instance('request', Request::create('/second'));
            $app->forgetScopedInstances();

            self::assertSame($owner, $app->make(ProviderOwner::class));
            self::assertSame($through, $app->make(ProviderListOwner::class));

            // Laravel 11 does not have the native #[Singleton] attribute.
            // Its plain bind() maps to the safe concrete but stays transient.
            if (class_exists(Singleton::class)) {
                self::assertSame($mapped, $app->make(HiddenConstructorRoot::class));
            } else {
                self::assertNotSame($mapped, $app->make(HiddenConstructorRoot::class));
            }

            self::assertSame($firstRequest, $app->make(ProviderListOwner::class)->carrier->request);
        } finally {
            Container::setInstance($previous);
        }
    }
}

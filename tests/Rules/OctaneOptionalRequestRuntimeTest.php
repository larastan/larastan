<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ScopeCompatibilityNullableRequest\BoundOptionalRequestConsumer;
use ScopeCompatibilityRequestSubclass\CustomRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class OctaneOptionalRequestRuntimeTest extends TestCase
{
    public function testSymfonyAndIlluminateRequestAliasesResolveTheSameBoundObject(): void
    {
        $previous = Container::getInstance();

        try {
            $application = new Application();
            $current     = Request::create('/native-symfony-request');

            $application->instance('request', $current);

            self::assertSame('request', $application->getAlias(SymfonyRequest::class));
            self::assertSame($current, $application->make(Request::class));
            self::assertSame($current, $application->make(SymfonyRequest::class));
        } finally {
            Container::setInstance($previous);
        }
    }

    public function testUnboundCustomRequestSubclassIsNotTheActiveRequestAlias(): void
    {
        $previous = Container::getInstance();

        try {
            $application = new Application();
            $current     = Request::create('/current-request');
            $application->instance('request', $current);

            $custom = $application->make(CustomRequest::class);

            self::assertNotSame($current, $custom);
            self::assertNotSame($custom, $application->make(CustomRequest::class));
            self::assertSame($current, $application->make(Request::class));
        } finally {
            Container::setInstance($previous);
        }
    }

    public function testOptionalRequestIsInjectedWhenBoundToCurrentRequest(): void
    {
        $container = new Container();
        $container->alias('request', Request::class);

        $current = Request::create('/worker-request');
        $container->instance('request', $current);

        $singleton = $container->make(BoundOptionalRequestConsumer::class);

        self::assertSame($current, $singleton->request);
    }
}

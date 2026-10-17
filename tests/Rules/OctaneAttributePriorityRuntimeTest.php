<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use ScopeCompatibilityAttributePriority\ActuallyScoped;
use ScopeCompatibilityAttributePriority\SingletonWins;

use function class_exists;

final class OctaneAttributePriorityRuntimeTest extends TestCase
{
    public function testSingletonAttributeTakesPrecedenceOverScopedAttribute(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Singleton') || ! class_exists('Illuminate\\Container\\Attributes\\Scoped')) {
            self::markTestSkipped('Native container lifetime attributes are not available in Laravel 11.');
        }

        $previous = Container::getInstance();

        try {
            $application = new Application();

            $singleton = $application->make(SingletonWins::class);
            $scoped    = $application->make(ActuallyScoped::class);

            self::assertSame($singleton, $application->make(SingletonWins::class));
            self::assertSame($scoped, $application->make(ActuallyScoped::class));

            $application->forgetScopedInstances();

            self::assertSame($singleton, $application->make(SingletonWins::class));
            self::assertNotSame($scoped, $application->make(ActuallyScoped::class));
        } finally {
            Container::setInstance($previous);
        }
    }
}

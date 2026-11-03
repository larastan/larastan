<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\Octane\ContainerBindingCollector;
use Larastan\Larastan\Rules\Octane\ScopedDependencyDetector;
use Larastan\Larastan\Rules\Octane\SingletonAttributeCollector;
use Larastan\Larastan\Rules\Octane\SingletonLifetimeRule;
use PhpParser\Node;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use function class_exists;

/** @extends RuleTestCase<SingletonLifetimeRule> */
final class OctaneBindingRegistrationRegressionTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $provider = self::getContainer()->getByType(ReflectionProvider::class);

        return new SingletonLifetimeRule(new ScopedDependencyDetector($provider), $provider);
    }

    public function testNativeScopedRegistrationAndExplicitSingletonOverride(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Scoped')) {
            self::markTestSkipped('Native lifetime attributes are not available in Laravel 11.');
        }

        $this->analyse([__DIR__ . '/data/CaptiveScopeRegistrationOverrides.php'], [
            [
                'Singleton ScopeCompatibilityRegistrationOverrides\\PlainCapturingSingleton has a constructor dependency on a scoped service: ScopeCompatibilityRegistrationOverrides\\PlainCapturingSingleton -> ScopeCompatibilityRegistrationOverrides\\PlainContext.',
                36,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    /** @return list<Collector<Node, mixed>> */
    protected function getCollectors(): array
    {
        return [new ContainerBindingCollector(), new SingletonAttributeCollector()];
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

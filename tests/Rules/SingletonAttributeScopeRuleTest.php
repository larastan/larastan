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
final class SingletonAttributeScopeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $provider = self::getContainer()->getByType(ReflectionProvider::class);

        return new SingletonLifetimeRule(new ScopedDependencyDetector($provider), $provider);
    }

    public function testNativeContainerAttributes(): void
    {
        if (! class_exists('Illuminate\Container\Attributes\Scoped') || ! class_exists('Illuminate\Container\Attributes\Singleton')) {
            self::markTestSkipped('Native lifetime attributes are not available in Laravel 11.');
        }

        $this->analyse([__DIR__ . '/data/CaptiveScopeAttributes.php'], [
            ['Singleton ScopeAttributeCompatibility\\BadDirectSingleton has a constructor dependency on a scoped service: ScopeAttributeCompatibility\\BadDirectSingleton -> ScopeAttributeCompatibility\\TenantContext.', 23, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
            ['Singleton ScopeAttributeCompatibility\\BadIndirectSingleton has a constructor dependency on a scoped service: ScopeAttributeCompatibility\\BadIndirectSingleton -> ScopeAttributeCompatibility\\Repository -> ScopeAttributeCompatibility\\TenantContext.', 31, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
            ['Singleton ScopeAttributeCompatibility\\BadRequestSingleton has a constructor dependency on a scoped service: ScopeAttributeCompatibility\\BadRequestSingleton -> Illuminate\\Http\\Request.', 39, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
            ['Singleton ScopeAttributeCompatibility\\InheritedRequestSingleton has a constructor dependency on a scoped service: ScopeAttributeCompatibility\\InheritedRequestSingleton -> Illuminate\\Http\\Request.', 78, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
            ['Singleton ScopeAttributeCompatibility\\InheritedScopedSingleton has a constructor dependency on a scoped service: ScopeAttributeCompatibility\\InheritedScopedSingleton -> ScopeAttributeCompatibility\\TenantContext.', 90, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
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

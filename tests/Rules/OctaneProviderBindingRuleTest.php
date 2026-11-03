<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\Octane\ContainerBindingCollector;
use Larastan\Larastan\Rules\Octane\ScopedDependencyDetector;
use Larastan\Larastan\Rules\Octane\ServiceProviderBindingCollector;
use Larastan\Larastan\Rules\Octane\SingletonAttributeCollector;
use Larastan\Larastan\Rules\Octane\SingletonLifetimeRule;
use PhpParser\Node;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use function class_exists;

/** @extends RuleTestCase<SingletonLifetimeRule> */
final class OctaneProviderBindingRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $provider = self::getContainer()->getByType(ReflectionProvider::class);

        return new SingletonLifetimeRule(new ScopedDependencyDetector($provider), $provider);
    }

    /** @return list<Collector<Node, mixed>> */
    protected function getCollectors(): array
    {
        $provider = self::getContainer()->getByType(ReflectionProvider::class);

        return [
            new ContainerBindingCollector(),
            new SingletonAttributeCollector(),
            new ServiceProviderBindingCollector($provider),
        ];
    }

    public function testLiteralProviderSingletonsAndBindingsAreCollected(): void
    {
        $this->analyse([__DIR__ . '/data/OctaneProviderBindingAudit.php'], [
            [
                'Singleton OctaneProviderBindingAudit\\ProviderOwner has a constructor dependency on a scoped service: OctaneProviderBindingAudit\\ProviderOwner -> Illuminate\\Http\\Request.',
                51,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
            [
                'Singleton OctaneProviderBindingAudit\\ProviderListOwner has a constructor dependency on a scoped service: OctaneProviderBindingAudit\\ProviderListOwner -> OctaneProviderBindingAudit\\RequestCarrier -> OctaneProviderBindingAudit\\RequestCarrierConcrete -> Illuminate\\Http\\Request.',
                51,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    public function testNativeAttributeAliasesAreCaseInsensitive(): void
    {
        if (! class_exists('Illuminate\Container\Attributes\Singleton')) {
            self::markTestSkipped('Native singleton and scoped attributes require Laravel 12+.');
        }

        $this->analyse([__DIR__ . '/data/OctaneAttributeAliasAudit.php'], [
            [
                'Singleton OctaneAttributeAliasAudit\\TenantOwner has a constructor dependency on a scoped service: OctaneAttributeAliasAudit\\TenantOwner -> OctaneAttributeAliasAudit\\TenantContext.',
                15,
                'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.',
            ],
        ]);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

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

/** @extends RuleTestCase<SingletonLifetimeRule> */
final class OctaneFactoryBindingRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $provider = self::getContainer()->getByType(ReflectionProvider::class);

        return new SingletonLifetimeRule(new ScopedDependencyDetector($provider), $provider);
    }

    /** @return list<Collector<Node, mixed>> */
    protected function getCollectors(): array
    {
        return [new ContainerBindingCollector(), new SingletonAttributeCollector()];
    }

    public function testUnknownFactoryConcreteDoesNotInventConstructorDependencies(): void
    {
        $this->analyse([__DIR__ . '/data/CaptiveScopeFactoryBinding.php'], [
            [
                'Singleton ScopeFactoryBinding\\SingletonWithSelfBinding has a constructor dependency on a scoped service: ScopeFactoryBinding\\SingletonWithSelfBinding -> ScopeFactoryBinding\\SelfBoundService -> Illuminate\\Http\\Request.',
                92,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
            [
                'Singleton ScopeFactoryBinding\\SingletonWithScopedFactory has a constructor dependency on a scoped service: ScopeFactoryBinding\\SingletonWithScopedFactory -> ScopeFactoryBinding\\FactoryScopedContext.',
                96,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

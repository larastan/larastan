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
final class SingletonCollectedBindingsRuleTest extends RuleTestCase
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

    public function testBindingsAcrossFilesWithInterfacesAndConflicts(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Scoped') || ! class_exists('Illuminate\\Container\\Attributes\\Singleton')) {
            self::markTestSkipped('Native lifetime attributes are not available in Laravel 11.');
        }

        $this->analyse([
            __DIR__ . '/data/CaptiveScopeCollectedServices.php',
            __DIR__ . '/data/CaptiveScopeCollectedRegistrations.php',
        ], [
            ['Singleton ScopeAcrossFiles\\ContextOwner has a constructor dependency on a scoped service: ScopeAcrossFiles\\ContextOwner -> ScopeAcrossFiles\\ScopedContext.', 12, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
            ['Singleton ScopeAcrossFiles\\ContractOwner has a constructor dependency on a scoped service: ScopeAcrossFiles\\ContractOwner -> ScopeAcrossFiles\\ScopedContract.', 15, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
            ['Singleton ScopeAcrossFiles\\TransientOwner has a constructor dependency on a scoped service: ScopeAcrossFiles\\TransientOwner -> ScopeAcrossFiles\\TransientContract -> ScopeAcrossFiles\\TransientImplementation -> Illuminate\\Http\\Request.', 18, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
            ['Singleton ScopeAcrossFiles\\DelegateOwner has a constructor dependency on a scoped service: ScopeAcrossFiles\\DelegateOwner -> ScopeAcrossFiles\\DelegateContract -> ScopeAcrossFiles\\ScopedDelegate.', 21, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
        ]);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

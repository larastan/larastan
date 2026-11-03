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
final class OctaneBindingSemanticRuleTest extends RuleTestCase
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

    public function testAttributedRootsUseEffectiveConstruction(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Singleton')) {
            self::markTestSkipped('Native lifetime attributes require Laravel 12+.');
        }

        $this->analyse([__DIR__ . '/data/OctaneSemanticRoots.php'], [
            ['Singleton OctaneSemanticRoots\\RootMappedCaptive has a constructor dependency on a scoped service: OctaneSemanticRoots\\RootMappedCaptive -> OctaneSemanticRoots\\CaptiveMappedConcrete -> Illuminate\\Http\\Request.', 42, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
            ['Singleton OctaneSemanticRoots\\RootToScoped has a constructor dependency on a scoped service: OctaneSemanticRoots\\RootToScoped -> OctaneSemanticRoots\\ScopedMappedConcrete.', 57, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
            ['Singleton OctaneSemanticRoots\\RootSelf has a constructor dependency on a scoped service: OctaneSemanticRoots\\RootSelf -> Illuminate\\Http\\Request.', 82, 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'],
        ]);
    }

    public function testSharedBindAndBindIfAreRealSingletons(): void
    {
        $this->analyse([__DIR__ . '/data/OctaneSemanticShared.php'], [
            ['Singleton OctaneSemanticShared\\NamedShared has a constructor dependency on a scoped service: OctaneSemanticShared\\NamedShared -> Illuminate\\Http\\Request.', 47, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
            ['Singleton OctaneSemanticShared\\PositionalShared has a constructor dependency on a scoped service: OctaneSemanticShared\\PositionalShared -> Illuminate\\Http\\Request.', 48, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
            ['Singleton OctaneSemanticShared\\SharedViaBindIf has a constructor dependency on a scoped service: OctaneSemanticShared\\SharedViaBindIf -> Illuminate\\Http\\Request.', 49, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
        ]);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

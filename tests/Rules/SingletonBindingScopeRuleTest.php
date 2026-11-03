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
final class SingletonBindingScopeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $provider = self::getContainer()->getByType(ReflectionProvider::class);

        return new SingletonLifetimeRule(new ScopedDependencyDetector($provider), $provider);
    }

    public function testNativeServiceContainerLifetimes(): void
    {
        if (! class_exists('Illuminate\Container\Attributes\Scoped') || ! class_exists('Illuminate\Container\Attributes\Singleton')) {
            self::markTestSkipped('Native lifetime attributes are not available in Laravel 11.');
        }

        $this->analyse([__DIR__ . '/data/CaptiveScopeBindings.php'], [
            ['Singleton ScopeCompatibility\\CurrentRequestService has a constructor dependency on a scoped service: ScopeCompatibility\\CurrentRequestService -> Illuminate\\Http\\Request.', 82, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
        ]);
    }

    public function testExplicitSingletonOverridesScopedMarkerOnTheOwner(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Scoped')) {
            self::markTestSkipped('Native lifetime attributes are not available in Laravel 11.');
        }

        $this->analyse([__DIR__ . '/data/CaptiveScopeExplicitSingleton.php'], [
            [
                'Singleton ScopeCompatibilityExplicitSingleton\\ScopeMarkedAndCapturesRequest has a constructor dependency on a scoped service: ScopeCompatibilityExplicitSingleton\\ScopeMarkedAndCapturesRequest -> Illuminate\\Http\\Request.',
                28,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    public function testRequestLifetimeDependencyOnAllSupportedLaravelVersions(): void
    {
        $this->analyse([__DIR__ . '/data/CaptiveScopeRequest.php'], [
            ['Singleton ScopeCompatibilityRequest\RequestConsumer has a constructor dependency on a scoped service: ScopeCompatibilityRequest\RequestConsumer -> Illuminate\Http\Request.', 19, 'Register the owner as scoped(), or resolve the current context when handling each request/job.'],
        ]);
    }

    public function testUnboundRequestSubclassIsNotTheActiveRequestAlias(): void
    {
        // Laravel aliases only the exact Request base classes to 'request'.
        // A custom subclass is a transient autowiring target unless bound.
        $this->analyse([__DIR__ . '/data/CaptiveScopeRequestSubclass.php'], []);
    }

    public function testExplicitlyScopedRequestSubclassStillReportsCaptiveDependency(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Scoped')) {
            self::markTestSkipped('Native lifetime attributes are not available in Laravel 11.');
        }

        $this->analyse([__DIR__ . '/data/CaptiveScopeExplicitScopedRequestSubclass.php'], [
            [
                'Singleton ScopeCompatibilityRequestSubclassScoped\\CaptiveSingleton has a constructor dependency on a scoped service: ScopeCompatibilityRequestSubclassScoped\\CaptiveSingleton -> ScopeCompatibilityRequestSubclassScoped\\ExplicitScopedRequest.',
                25,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    public function testSingletonAttributeWinsOverScopedAttributeOnDependency(): void
    {
        if (! class_exists('Illuminate\\Container\\Attributes\\Scoped') || ! class_exists('Illuminate\\Container\\Attributes\\Singleton')) {
            self::markTestSkipped('Native lifetime attributes are not available in Laravel 11.');
        }

        $this->analyse([__DIR__ . '/data/CaptiveScopeConflictingAttributes.php'], [
            [
                'Singleton ScopeCompatibilityAttributePriority\\HoldsScoped has a constructor dependency on a scoped service: ScopeCompatibilityAttributePriority\\HoldsScoped -> ScopeCompatibilityAttributePriority\\ActuallyScoped.',
                39,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    public function testDepthLimitedPathDoesNotHideShorterCaptiveDependency(): void
    {
        $this->analyse([__DIR__ . '/data/CaptiveScopeDiamond.php'], [
            [
                'Singleton ScopeCompatibilityDiamond\\Root has a constructor dependency on a scoped service: ScopeCompatibilityDiamond\\Root -> ScopeCompatibilityDiamond\\ShortPath -> ScopeCompatibilityDiamond\\Shared -> ScopeCompatibilityDiamond\\Intermediate -> Illuminate\\Http\\Request.',
                85,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
            [
                'Singleton ScopeCompatibilityDiamond\\PrioritizedRoot has a constructor dependency on a scoped service: ScopeCompatibilityDiamond\\PrioritizedRoot -> Illuminate\\Http\\Request.',
                97,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    public function testRequiredNullableRequestIsStillResolvedFromContainer(): void
    {
        $this->analyse([__DIR__ . '/data/CaptiveScopeNullableRequest.php'], [
            [
                'Singleton ScopeCompatibilityNullableRequest\\RequiredNullableConsumer has a constructor dependency on a scoped service: ScopeCompatibilityNullableRequest\\RequiredNullableConsumer -> Illuminate\\Http\\Request.',
                33,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
            [
                'Singleton ScopeCompatibilityNullableRequest\\OptionalNullableConsumer has a constructor dependency on a scoped service: ScopeCompatibilityNullableRequest\\OptionalNullableConsumer -> Illuminate\\Http\\Request.',
                34,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
            [
                'Singleton ScopeCompatibilityNullableRequest\\BoundOptionalRequestConsumer has a constructor dependency on a scoped service: ScopeCompatibilityNullableRequest\\BoundOptionalRequestConsumer -> Illuminate\\Http\\Request.',
                35,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
        ]);
    }

    public function testNativeSymfonyRequestAliasIsBoundToCurrentRequest(): void
    {
        // Laravel Application aliases Symfony's base Request to 'request'.
        // Do not apply that special case to arbitrary Symfony-only subclasses.
        $this->analyse([__DIR__ . '/data/CaptiveScopeSymfonyRequest.php'], [
            [
                'Singleton ScopeCompatibilitySymfonyRequest\\SymfonyAliasConsumer has a constructor dependency on a scoped service: ScopeCompatibilitySymfonyRequest\\SymfonyAliasConsumer -> Symfony\\Component\\HttpFoundation\\Request.',
                51,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
            [
                'Singleton ScopeCompatibilitySymfonyRequest\\TransitiveConsumer has a constructor dependency on a scoped service: ScopeCompatibilitySymfonyRequest\\TransitiveConsumer -> ScopeCompatibilitySymfonyRequest\\DependencyWrapper -> Symfony\\Component\\HttpFoundation\\Request.',
                52,
                'Register the owner as scoped(), or resolve the current context when handling each request/job.',
            ],
            [
                'Singleton ScopeCompatibilitySymfonyRequest\\OptionalSymfonyAliasConsumer has a constructor dependency on a scoped service: ScopeCompatibilitySymfonyRequest\\OptionalSymfonyAliasConsumer -> Symfony\\Component\\HttpFoundation\\Request.',
                54,
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

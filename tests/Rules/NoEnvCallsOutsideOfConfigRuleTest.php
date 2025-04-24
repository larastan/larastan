<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Foundation\Application;
use Larastan\Larastan\Rules\NoEnvCallsOutsideOfConfigRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoEnvCallsOutsideOfConfigRule> */
class NoEnvCallsOutsideOfConfigRuleTest extends RuleTestCase
{
    protected function setUp(): void
    {
        $this->overrideConfigPath(__DIR__ . '/data/config');
    }

    protected function getRule(): Rule
    {
        return new NoEnvCallsOutsideOfConfigRule([
            __DIR__ . '/data/config',
            __DIR__ . '/data/module/*/config',
        ], $this->getFileHelper());
    }

    /** @test */
    public function itDoesNotFailForEnvCallsInsideConfigDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/config/env-calls.php'], []);
    }

    /** @test */
    public function itDoesNotFailForEnvCallsInsideGlobConfigDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/module/foo/config/env-calls.php', __DIR__ . '/data/module/bar/config/env-calls.php'], []);
    }

    /** @test */
    public function itReportsEnvCallsOutsideOfConfigDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/env-calls.php'], [
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 7],
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 8],
        ]);
    }

    /** @test */
    public function itDoesNotReportTraitFunctionsThatHaveBeenOverridden(): void
    {
        $this->analyse([
            __DIR__ . '/data/EnvUsageClassOverride.php',
            __DIR__ . '/data/EnvUsageTrait.php',
        ], []);
    }

    /** @test */
    public function itReportsEnvCallsInTraitRatherThanClass(): void
    {
        $actualErrors = $this->gatherAnalyserErrors([
            __DIR__ . '/data/EnvUsageClass.php',
            __DIR__ . '/data/EnvUsageTrait.php',
        ]);

        $this->assertCount(2, $actualErrors);
        $this->assertSame(
            "Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.",
            $actualErrors[0]->getMessage(),
        );
        $this->assertSame(
            __DIR__ . '/data/EnvUsageTrait.php (in context of class Tests\Rules\Data\EnvUsageClass)',
            $actualErrors[0]->getFile(),
        );
        $this->assertSame(17, $actualErrors[0]->getLine());

        $this->assertSame(
            "Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.",
            $actualErrors[1]->getMessage(),
        );
        $this->assertSame(
            __DIR__ . '/data/EnvUsageTrait.php (in context of class Tests\Rules\Data\EnvUsageClass)',
            $actualErrors[1]->getFile(),
        );
        $this->assertSame(18, $actualErrors[1]->getLine());
    }

    protected function overrideConfigPath(string $path): void
    {
        $app = Application::getInstance();
        $app->useConfigPath($path);
    }
}

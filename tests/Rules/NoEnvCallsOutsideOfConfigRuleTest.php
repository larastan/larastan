<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Foundation\Application;
use Larastan\Larastan\Rules\NoEnvCallsOutsideOfConfigRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/** @extends RuleTestCase<NoEnvCallsOutsideOfConfigRule> */
class NoEnvCallsOutsideOfConfigRuleTest extends RuleTestCase
{
    /** @var array<int, string>|null */
    private array|null $originalArgv = null;

    protected function setUp(): void
    {
        $this->overrideConfigPath(__DIR__ . '/data/config');
        $this->originalArgv = $_SERVER['argv'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalArgv === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $this->originalArgv;
        }

        parent::tearDown();
    }

    protected function getRule(): Rule
    {
        return new NoEnvCallsOutsideOfConfigRule([
            __DIR__ . '/data/config',
            __DIR__ . '/data/module/*/config',
        ], $this->getFileHelper());
    }

    private function setEditorModeArgv(string|null $tmpFile, string|null $insteadOf): void
    {
        $argv = ['phpstan', 'analyse'];

        if ($tmpFile !== null) {
            $argv[] = '--tmp-file';
            $argv[] = $tmpFile;
        }

        if ($insteadOf !== null) {
            $argv[] = '--instead-of';
            $argv[] = $insteadOf;
        }

        $_SERVER['argv'] = $argv;
    }

    #[Test]
    public function itDoesNotFailForEnvCallsInsideConfigDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/config/env-calls.php'], []);
    }

    #[Test]
    public function itDoesNotFailForEnvCallsInsideGlobConfigDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/module/foo/config/env-calls.php', __DIR__ . '/data/module/bar/config/env-calls.php'], []);
    }

    #[Test]
    public function itReportsEnvCallsOutsideOfConfigDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/env-calls.php'], [
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 7],
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 8],
        ]);
    }

    #[Test]
    public function itDoesNotReportTraitFunctionsThatHaveBeenOverridden(): void
    {
        $this->analyse([
            __DIR__ . '/data/EnvUsageClassOverride.php',
            __DIR__ . '/data/EnvUsageTrait.php',
        ], []);
    }

    #[Test]
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

    #[Test]
    public function itDoesNotReportInEditorModeWhenInsteadOfPointsAtConfigFile(): void
    {
        $this->setEditorModeArgv(__DIR__ . '/data/env-calls.php', __DIR__ . '/data/config/env-calls.php');

        $this->analyse([__DIR__ . '/data/env-calls.php'], []);
    }

    #[Test]
    public function itDoesNotReportInEditorModeWhenInsteadOfIsMissing(): void
    {
        $this->setEditorModeArgv(__DIR__ . '/data/env-calls.php', null);

        $this->analyse([__DIR__ . '/data/env-calls.php'], []);
    }

    #[Test]
    public function itReportsInEditorModeWhenInsteadOfIsOutsideConfig(): void
    {
        $this->setEditorModeArgv(__DIR__ . '/data/env-calls.php', __DIR__ . '/data/some-non-config-file.php');

        $this->analyse([__DIR__ . '/data/env-calls.php'], [
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 7],
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 8],
        ]);
    }

    #[Test]
    public function itUsesNormalLogicWhenAnalysedFileDiffersFromTmpFile(): void
    {
        $this->setEditorModeArgv('/some/other/buffer.php', __DIR__ . '/data/config/env-calls.php');

        $this->analyse([__DIR__ . '/data/env-calls.php'], [
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 7],
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 8],
        ]);
    }

    protected function overrideConfigPath(string $path): void
    {
        $app = Application::getInstance();
        $app->useConfigPath($path);
    }
}

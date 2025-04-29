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
        $this->overrideConfigPath(__DIR__.'/data/config');
    }

    protected function getRule(): Rule
    {
        return new NoEnvCallsOutsideOfConfigRule([__DIR__.'/data/config'], $this->getFileHelper());
    }

    /** @test */
    public function it_does_not_fail_for_env_calls_inside_config_directory(): void
    {
        $this->analyse([__DIR__.'/data/config/env-calls.php'], []);
    }

    /** @test */
    public function it_reports_env_calls_outside_of_config_directory(): void
    {
        $this->analyse([__DIR__.'/data/env-calls.php'], [
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 7],
            ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 8],
        ]);
    }

    /** @test */
    public function it_does_not_report_trait_functions_that_have_been_overridden(): void
    {
        $this->analyse([
            __DIR__.'/data/EnvUsageClassOverride.php',
            __DIR__.'/data/EnvUsageTrait.php',
        ], []);
    }

    /** @test */
    public function it_reports_env_calls_in_trait_rather_than_class(): void
    {
        $actualErrors = $this->gatherAnalyserErrors([
            __DIR__.'/data/EnvUsageClass.php',
            __DIR__.'/data/EnvUsageTrait.php',
        ]);

        $this->assertCount(2, $actualErrors);
        $this->assertSame(
            "Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.",
            $actualErrors[0]->getMessage(),
        );
        $this->assertSame(
            __DIR__.'/data/EnvUsageTrait.php (in context of class Tests\Rules\Data\EnvUsageClass)',
            $actualErrors[0]->getFile(),
        );
        $this->assertSame(17, $actualErrors[0]->getLine());

        $this->assertSame(
            "Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.",
            $actualErrors[1]->getMessage(),
        );
        $this->assertSame(
            __DIR__.'/data/EnvUsageTrait.php (in context of class Tests\Rules\Data\EnvUsageClass)',
            $actualErrors[1]->getFile(),
        );
        $this->assertSame(18, $actualErrors[1]->getLine());
    }

    protected function overrideConfigPath(string $path): void
    {
        $app = Application::getInstance();
        $app->useConfigPath($path);
    }

    /** @test */
    public function it_handles_windows_paths_correctly(): void
    {
        // Skip this test on non-Windows systems
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('This test is only relevant on Windows systems');
        }

        // Create a test file with env() calls
        $testFile = __DIR__.'/data/windows-test-env-calls.php';
        file_put_contents($testFile, '<?php env("foo"); ?>');

        try {
            // Test file outside config directory (should report)
            $testFilePath = str_replace('/', '\\', $testFile);
            $this->analyse([$testFilePath], [
                ["Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.", 1],
            ]);

            // Test file inside config directory (should not report)
            $configFile = __DIR__.'/data/config/windows-test.php';
            file_put_contents($configFile, '<?php env("foo"); ?>');
            $configFilePath = str_replace('/', '\\', $configFile);

            $this->analyse([$configFilePath], []);
        } finally {
            // Clean up test files
            if (file_exists($testFile)) {
                unlink($testFile);
            }
            if (isset($configFile) && file_exists($configFile)) {
                unlink($configFile);
            }
        }
    }
}

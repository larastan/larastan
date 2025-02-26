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
        return new NoEnvCallsOutsideOfConfigRule([__DIR__ . '/data/config'], $this->getFileHelper());
    }

    /** @test */
    public function itDoesNotFailForEnvCallsInsideConfigDirectory(): void
    {
        $this->analyse([__DIR__ . '/data/config/env-calls.php'], []);
    }

    /** @test */
    public function itReportsEnvCallsOutsideOfConfigDirectory(): void
    {
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

<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Foundation\Application;
use Larastan\Larastan\Rules\NoEnvCallsOutsideOfConfigRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use ReflectionClass;

use function str_replace;
use function version_compare;

/** @extends RuleTestCase<NoEnvCallsOutsideOfConfigRule> */
class NoEnvCallsOutsideOfConfigRuleTest extends RuleTestCase
{
    protected function setUp(): void
    {
        $this->overrideConfigPath(__DIR__ . '/data/config');
    }

    protected function getRule(): Rule
    {
        return new NoEnvCallsOutsideOfConfigRule();
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

        if (version_compare(LARAVEL_VERSION, '10.0.0', '>=')) {
            $app->useConfigPath($path);

            return;
        }

        $reflectionClass = new ReflectionClass($app);
        $property        = $reflectionClass->getProperty('basePath');
        $property->setAccessible(true);

        $property->setValue($app, str_replace('/config', '', $path));
    }
}

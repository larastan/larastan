<?php

declare(strict_types=1);

namespace Rules;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Larastan\Larastan\Rules\EnvironmentFileDependencyRule;
use PhpParser\Node\Stmt\Nop;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<EnvironmentFileDependencyRule> */
class EnvironmentFileDependencyRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new EnvironmentFileDependencyRule();
    }

    public function testTracksEnvironmentFile(): void
    {
        $tracked = [];
        $scope   = $this->createStubForIntersectionOfInterfaces([Scope::class, DependencyTracker::class]);
        $scope->method('trackFileDependency')->willReturnCallback(static function (string $file) use (&$tracked): void {
            $tracked[] = $file;
        });

        $application = new Application(__DIR__ . '/../application');
        $application->loadEnvironmentFrom('.env.phpstan');

        $rule = $this->getRule();
        $rule->setContainer($application);

        $this->assertSame([], $rule->processNode(new Nop(), $scope));
        $this->assertSame([__DIR__ . '/../application/.env.phpstan'], $tracked);
    }

    public function testIgnoresNonApplicationContainer(): void
    {
        $tracked = [];
        $scope   = $this->createStubForIntersectionOfInterfaces([Scope::class, DependencyTracker::class]);
        $scope->method('trackFileDependency')->willReturnCallback(static function (string $file) use (&$tracked): void {
            $tracked[] = $file;
        });

        $rule = $this->getRule();
        $rule->setContainer(new Container());

        $this->assertSame([], $rule->processNode(new Nop(), $scope));
        $this->assertSame([], $tracked);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }
}

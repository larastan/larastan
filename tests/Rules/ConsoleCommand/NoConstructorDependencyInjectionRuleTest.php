<?php

declare(strict_types=1);

namespace Tests\Rules\ConsoleCommand;

use Larastan\Larastan\Rules\ConsoleCommand\NoConstructorDependencyInjectionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/** @extends RuleTestCase<NoConstructorDependencyInjectionRule> */
class NoConstructorDependencyInjectionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoConstructorDependencyInjectionRule();
    }

    #[Test]
    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/console-command-constructor-injection.php'], [
            [
                'Console command "Tests\Rules\ConsoleCommand\Data\CommandWithConstructorDI" should not have constructor arguments. Use dependency injection in the handle() or __invoke() method instead.',
                9,
                'Move all dependencies to the handle() or __invoke() method parameters for better testability and Laravel best practices.',
            ],
            [
                'Console command "Tests\Rules\ConsoleCommand\Data\CommandWithMultipleConstructorArgs" should not have constructor arguments. Use dependency injection in the handle() or __invoke() method instead.',
                43,
                'Move all dependencies to the handle() or __invoke() method parameters for better testability and Laravel best practices.',
            ],
            [
                'Console command "Tests\Rules\ConsoleCommand\Data\InvokableCommandWithConstructorDI" should not have constructor arguments. Use dependency injection in the handle() or __invoke() method instead.',
                82,
                'Move all dependencies to the handle() or __invoke() method parameters for better testability and Laravel best practices.',
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Tests\Rules\ConsoleCommand;

use Larastan\Larastan\Rules\ConsoleCommand\NoConstructorDependencyInjectionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoConstructorDependencyInjectionRule> */
class NoConstructorDependencyInjectionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoConstructorDependencyInjectionRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/console-command-constructor-injection.php'], [
            [
                'Console command "Larastan\Larastan\Tests\Rules\ConsoleCommand\data\CommandWithConstructorDI" should not have constructor arguments. Use dependency injection in the handle() method instead.',
                9,
                'Move all dependencies to the handle() method parameters for better testability and Laravel best practices.',
            ],
            [
                'Console command "Larastan\Larastan\Tests\Rules\ConsoleCommand\data\CommandWithMultipleConstructorArgs" should not have constructor arguments. Use dependency injection in the handle() method instead.',
                43,
                'Move all dependencies to the handle() method parameters for better testability and Laravel best practices.',
            ],
        ]);
    }
}

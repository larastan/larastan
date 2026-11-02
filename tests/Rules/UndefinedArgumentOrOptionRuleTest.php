<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Internal\ConsoleApplicationResolver;
use Larastan\Larastan\Rules\ConsoleCommand\UndefinedArgumentOrOptionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UndefinedArgumentOrOptionRule> */
class UndefinedArgumentOrOptionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new UndefinedArgumentOrOptionRule(self::getContainer()->getByType(ConsoleApplicationResolver::class));
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/../application/app/Console/Commands/FooCommand.php',
            __DIR__ . '/../application/app/Console/Commands/BarCommand.php',
            __DIR__ . '/../application/app/Console/Commands/BazCommand.php',
        ], []);
    }

    public function testRuleWithCommandsRegisteredByTheConsoleKernel(): void
    {
        $this->analyse([
            __DIR__ . '/../application/app/Console/Commands/RegisteredInKernelCommand.php',
            __DIR__ . '/../application/app/Console/LoadedCommands/LoadedByKernelCommand.php',
        ], [
            ['Command "registered-in-kernel" does not have argument "undefinedArgument".', 28],
            ['Command "registered-in-kernel" does not have option "undefinedOption".', 29],
            ['Command "loaded-by-kernel" does not have argument "undefinedArgument".', 28],
            ['Command "loaded-by-kernel" does not have option "undefinedOption".', 29],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/phpstan-rules.neon',
        ];
    }
}

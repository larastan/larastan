<?php

declare(strict_types=1);

namespace Tests\Rules;

use NunoMaduro\Larastan\Internal\ConsoleApplicationResolver;
use NunoMaduro\Larastan\Rules\ConsoleCommand\UndefinedArgumentOrOptionRule;
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
            __DIR__.'/../application/app/Console/Commands/FooCommand.php',
            __DIR__.'/../application/app/Console/Commands/BarCommand.php',
            __DIR__.'/../application/app/Console/Commands/BazCommand.php',
        ], [
            [
                'Command "foo" does not have argument "foobar".',
                22,
            ],
            [
                'Command "foo" does not have option "foobar".',
                36,
            ],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/phpstan-rules.neon',
        ];
    }
}

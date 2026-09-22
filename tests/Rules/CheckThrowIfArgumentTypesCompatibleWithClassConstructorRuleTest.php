<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\CheckThrowIfArgumentTypesCompatibleWithClassConstructorRule;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<CheckThrowIfArgumentTypesCompatibleWithClassConstructorRule> */
class CheckThrowIfArgumentTypesCompatibleWithClassConstructorRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CheckThrowIfArgumentTypesCompatibleWithClassConstructorRule(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(FunctionCallParametersCheck::class),
        );
    }

    public function testThrowIf(): void
    {
        $this->analyse([__DIR__ . '/data/throw-if.php'], [
            ['Exception class Tests\Rules\Data\ThrowIfException constructor invoked with 0 parameters in throw_if(), 2 required.', 20],
            ['Exception class Tests\Rules\Data\ThrowIfException constructor invoked with 1 parameter in throw_if(), 2 required.', 21],
            ['Exception class Tests\Rules\Data\ThrowIfException constructor invoked with 3 parameters in throw_if(), 2 required.', 22],
            ['Exception class Tests\Rules\Data\ThrowIfException constructor invoked with 1 parameter in throw_unless(), 2 required.', 23],
            ['Parameter #1 $foo of exception class Tests\Rules\Data\ThrowIfException constructor expects string in throw_if(), int given.', 28],
            ['Parameter #2 $bar of exception class Tests\Rules\Data\ThrowIfException constructor expects int in throw_if(), string given.', 28],
            ['Parameter #1 $foo of exception class Tests\Rules\Data\ThrowIfException constructor expects string in throw_unless(), true given.', 29],
            ['Parameter #2 $bar of exception class Tests\Rules\Data\ThrowIfException constructor expects int in throw_unless(), false given.', 29],
            ['Exception class Tests\Rules\Data\ThrowIfException constructor invoked with 0 parameters in throw_if(), 2 required.', 34],
            ['Exception class Tests\Rules\Data\ThrowIfException constructor invoked with 0 parameters in throw_if(), 2 required.', 35],
            ['Parameter $bar of exception class Tests\Rules\Data\ThrowIfException constructor expects int in throw_if(), string given.', 37],
            ['Parameter $foo of exception class Tests\Rules\Data\ThrowIfException constructor expects string in throw_if(), int given.', 37],
            ['Missing parameter $bar (int) in call to Tests\Rules\Data\ThrowIfException constructor.', 38],
            ['Unknown parameter $baz in call to Tests\Rules\Data\ThrowIfException constructor.', 38],
            ['Function throw_if() is called with a string that is not a class name, so a RuntimeException is thrown with that string as its message and the given parameters are ignored.', 52],
            ['Class Tests\Rules\Data\ThrowIfNotAnException given to throw_if() does not implement Throwable.', 53],
            ['Parameter #2 $bar of exception class Tests\Rules\Data\ThrowIfException constructor expects int in throw_if(), null given.', 77],
            ['Parameter #2 $bar of exception class Tests\Rules\Data\ThrowIfException constructor expects int in throw_if(), int|null given.', 82],
        ]);
    }
}

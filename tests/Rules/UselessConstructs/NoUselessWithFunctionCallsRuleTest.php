<?php

declare(strict_types=1);

namespace Rules\UselessConstructs;

use NunoMaduro\Larastan\Rules\UselessConstructs\NoUselessWithFunctionCallsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoUselessWithFunctionCallsRule> */
class NoUselessWithFunctionCallsRuleTest extends RuleTestCase
{
    public function testNoFalsePositives(): void
    {
        $this->analyse(
            [
                __DIR__.'/../Data/UselessConstructs/CorrectWithFunctionCall.php',
            ],
            []
        );
    }

    public function testUselessWithCalls(): void
    {
        $this->analyse(
            [
                __DIR__.'/../Data/UselessConstructs/UselessWithFunctionCall.php',
            ],
            [
                ["Calling the helper function 'with()' with only one argument simply returns the value itself. If you want to chain methods on a construct, use '(new ClassName())->foo()' instead", 11],
                ["Calling the helper function 'with()' without a callable as the second argument simply returns the value without doing anything", 16],
            ]
        );
    }

    protected function getRule(): Rule
    {
        return new NoUselessWithFunctionCallsRule();
    }
}

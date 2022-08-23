<?php

declare(strict_types=1);

namespace Rules\UselessConstructs;

use NunoMaduro\Larastan\Rules\UselessConstructs\NoUselessValueFunctionCallsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoUselessValueFunctionCallsRule> */
class NoUselessValueFunctionCallsRuleTest extends RuleTestCase
{
    public function testNoFalsePositives(): void
    {
        $this->analyse(
            [
                __DIR__.'/../Data/UselessConstructs/CorrectValueFunctionCall.php',
            ],
            []
        );
    }

    public function testUselessWithCalls(): void
    {
        $this->analyse(
            [
                __DIR__.'/../Data/UselessConstructs/UselessValueFunctionCall.php',
            ],
            [
                ["Calling the helper function 'value()' without a closure as the first argument simply returns the first argument without doing anything", 11],
            ]
        );
    }

    protected function getRule(): Rule
    {
        return new NoUselessValueFunctionCallsRule();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoUnnecessaryEnumerableToArrayCallsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoUnnecessaryEnumerableToArrayCallsRule> */
class NoUnnecessaryEnumerableToArrayCallsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUnnecessaryEnumerableToArrayCallsRule();
    }

    public function test_rule(): void
    {
        $message = "Called [toArray()] on an Enumerable which does not contain any Arrayables.\n    💡 Use [all()] to get the items as an array.";

        $this->analyse([__DIR__ . '/data/unnecessary-enumerable-toArray-calls.php'], [
            [$message, 27],
        ]);
    }
}

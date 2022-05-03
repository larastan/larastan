<?php

declare(strict_types=1);

namespace Rules\UselessConstructs;

use Tests\RulesTest;

class NoUselessValueFunctionCallsRuleTest extends RulesTest
{
    public function testNoFalsePositives(): void
    {
        $errors = $this->findErrorsByLine(dirname(__DIR__).'/Data/UselessConstructs/CorrectValueFunctionCall.php');
        $this->assertEquals([], $errors, 'The rule should not result in any errors for this data set.');
    }

    public function testUselessWithCalls(): void
    {
        $errors = $this->findErrorsByLine(dirname(__DIR__).'/Data/UselessConstructs/UselessValueFunctionCall.php');

        self::assertEquals([
            12 => "Calling the helper function 'value()' without a closure as the first argument simply returns the first argument without doing anything",
        ], $errors);
    }
}

<?php

declare(strict_types=1);

namespace Rules\UselessConstructs;

use Tests\RulesTest;

class NoUselessWithFunctionCallsRuleTest extends RulesTest
{
    public function testNoFalsePositives(): void
    {
        $errors = $this->findErrorsByLine(dirname(__DIR__).'/Data/UselessConstructs/CorrectWithFunctionCall.php');
        $this->assertEquals([], $errors, 'The rule should not result in any errors for this data set.');
    }

    public function testUselessWithCalls(): void
    {
        $errors = $this->findErrorsByLine(dirname(__DIR__).'/Data/UselessConstructs/UselessWithFunctionCall.php');

        self::assertEquals([
            11 => "Calling the helper function 'with()' with only one argument simply returns the value itself. if you want to chain methods on a construct, use '(new ClassName())->foo()' instead",
            16 => "Calling the helper function 'with()' without a closure as the second argument simply returns the value without doing anything",
        ], $errors);
    }
}

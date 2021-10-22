<?php

declare(strict_types=1);

namespace Tests\Rules;

use Tests\RulesTest;

class NoModelMakeRuleTest extends RulesTest
{
    public function testNoFalsePositives(): void
    {
        $errors = $this->findErrors(__DIR__.'/Data/CorrectModelInstantiation.php');
        $this->assertEquals([], $errors, 'The rule should not result in any errors for this data set.');
    }

    public function testModelMake(): void
    {
        $errors = $this->findErrorsByLine(__DIR__.'/Data/ModelMake.php');

        self::assertEquals([
            13 => "Called 'Model::make()' which performs unnecessary work, use 'new Model()'.",
            20 => "Called 'Model::make()' which performs unnecessary work, use 'new Model()'.",
        ], $errors);
    }
}

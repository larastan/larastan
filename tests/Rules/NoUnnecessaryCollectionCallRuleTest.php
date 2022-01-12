<?php

declare(strict_types=1);

namespace Tests\Rules;

use Tests\RulesTest;

class NoUnnecessaryCollectionCallRuleTest extends RulesTest
{
    public function testNoFalsePositives(): void
    {
        $errors = $this->findErrors(__DIR__.'/Data/CorrectCollectionCalls.php');
        $this->assertEquals([], $errors, 'The rule should not result in any errors for this data set.');
    }

    public function testNoFalseNegativesEloquent(): void
    {
        $errors = $this->findErrorsByLine(__DIR__.'/Data/UnnecessaryCollectionCallsEloquent.php');

        $this->assertEquals([
            19 => 'Called \'pluck\' on Laravel collection, but could have been retrieved as a query.',
            24 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            30 => 'Called \'pluck\' on Laravel collection, but could have been retrieved as a query.',
            35 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            40 => 'Called \'first\' on Laravel collection, but could have been retrieved as a query.',
            46 => 'Called \'take\' on Laravel collection, but could have been retrieved as a query.',
            51 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            58 => 'Called \'isEmpty\' on Laravel collection, but could have been retrieved as a query.',
            63 => 'Called \'first\' on Laravel collection, but could have been retrieved as a query.',
            68 => 'Called \'contains\' on Laravel collection, but could have been retrieved as a query.',
            73 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            79 => 'Called \'where\' on Laravel collection, but could have been retrieved as a query.',
            85 => 'Called \'diff\' on Laravel collection, but could have been retrieved as a query.',
            93 => 'Called \'modelKeys\' on Laravel collection, but could have been retrieved as a query.',
            98 => 'Called \'containsStrict\' on Laravel collection, but could have been retrieved as a query.',
            104 => 'Called \'sum\' on Laravel collection, but could have been retrieved as a query.',
        ], $errors);
    }

    public function testNoFalseNegativesQuery(): void
    {
        $this->assertSeeErrorsInOrder(__DIR__.'/Data/UnnecessaryCollectionCallsQuery.php', [
            'Called \'max\' on Laravel collection, but could have been retrieved as a query.',
            'Called \'isNotEmpty\' on Laravel collection, but could have been retrieved as a query.',
            'Called \'pluck\' on Laravel collection, but could have been retrieved as a query.',
        ]);
    }
}

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
            18 => 'Called \'pluck\' on Laravel collection, but could have been retrieved as a query.',
            23 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            29 => 'Called \'pluck\' on Laravel collection, but could have been retrieved as a query.',
            34 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            39 => 'Called \'first\' on Laravel collection, but could have been retrieved as a query.',
            45 => 'Called \'take\' on Laravel collection, but could have been retrieved as a query.',
            50 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            57 => 'Called \'isEmpty\' on Laravel collection, but could have been retrieved as a query.',
            62 => 'Called \'first\' on Laravel collection, but could have been retrieved as a query.',
            67 => 'Called \'contains\' on Laravel collection, but could have been retrieved as a query.',
            72 => 'Called \'count\' on Laravel collection, but could have been retrieved as a query.',
            78 => 'Called \'where\' on Laravel collection, but could have been retrieved as a query.',
            84 => 'Called \'diff\' on Laravel collection, but could have been retrieved as a query.',
            92 => 'Called \'modelKeys\' on Laravel collection, but could have been retrieved as a query.',
            97 => 'Called \'containsStrict\' on Laravel collection, but could have been retrieved as a query.',
            103 => 'Called \'sum\' on Laravel collection, but could have been retrieved as a query.',
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

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
            15 => '[NoUnnecessaryCollectionCallRule] Called \'pluck\' on collection, but could have been retrieved as a query.',
            20 => '[NoUnnecessaryCollectionCallRule] Called \'count\' on collection, but could have been retrieved as a query.',
            25 => '[NoUnnecessaryCollectionCallRule] Called \'pluck\' on collection, but could have been retrieved as a query.',
            30 => '[NoUnnecessaryCollectionCallRule] Called \'count\' on collection, but could have been retrieved as a query.',
            35 => '[NoUnnecessaryCollectionCallRule] Called \'first\' on collection, but could have been retrieved as a query.',
            40 => '[NoUnnecessaryCollectionCallRule] Called \'take\' on collection, but could have been retrieved as a query.',
            45 => '[NoUnnecessaryCollectionCallRule] Called \'count\' on collection, but could have been retrieved as a query.',
            52 => '[NoUnnecessaryCollectionCallRule] Called \'isEmpty\' on collection, but could have been retrieved as a query.',
            57 => '[NoUnnecessaryCollectionCallRule] Called \'first\' on collection, but could have been retrieved as a query.',
            62 => '[NoUnnecessaryCollectionCallRule] Called \'contains\' on collection, but could have been retrieved as a query.',
            67 => '[NoUnnecessaryCollectionCallRule] Called \'count\' on collection, but could have been retrieved as a query.',
            72 => '[NoUnnecessaryCollectionCallRule] Called \'where\' on collection, but could have been retrieved as a query.',
            77 => '[NoUnnecessaryCollectionCallRule] Called \'diff\' on collection, but could have been retrieved as a query.',
            85 => '[NoUnnecessaryCollectionCallRule] Called \'modelKeys\' on collection, but could have been retrieved as a query.',
            90 => '[NoUnnecessaryCollectionCallRule] Called \'containsStrict\' on collection, but could have been retrieved as a query.',
        ], $errors);
    }

    public function testNoFalseNegativesQuery(): void
    {
        $this->assertSeeErrorsInOrder(__DIR__.'/Data/UnnecessaryCollectionCallsQuery.php', [
            '[NoUnnecessaryCollectionCallRule] Called \'max\' on collection, but could have been retrieved as a query.',
            '[NoUnnecessaryCollectionCallRule] Called \'average\' on collection, but could have been retrieved as a query.',
            '[NoUnnecessaryCollectionCallRule] Called \'isNotEmpty\' on collection, but could have been retrieved as a query.',
            '[NoUnnecessaryCollectionCallRule] Called \'pluck\' on collection, but could have been retrieved as a query.',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Rules;

use Tests\RulesTest;

class NoUnnecessaryCollectionCallRuleTest extends RulesTest
{
    public function testNoFalsePositives(): void
    {
        $errors = $this->findErrors(__DIR__ . '/Data/CorrectCollectionCalls.php');
        $this->assertEquals([], $errors, 'The rule should not result in any errors for this data set.');
    }

    public function testNoFalseNegativesEloquent(): void
    {
        $errors = $this->findErrorsByLine(__DIR__ . '/Data/UnnecessaryCollectionCallsEloquent.php');

        $this->assertEquals($errors, [
            15 => 'Called \'pluck\' on collection, but could have been retrieved as a query.',
            20 => 'Called \'count\' on collection, but could have been retrieved as a query.',
            25 => 'Called \'pluck\' on collection, but could have been retrieved as a query.',
            30 => 'Called \'count\' on collection, but could have been retrieved as a query.',
            35 => 'Called \'first\' on collection, but could have been retrieved as a query.',
            40 => 'Called \'take\' on collection, but could have been retrieved as a query.',
            45 => 'Called \'count\' on collection, but could have been retrieved as a query.',
            52 => 'Called \'isEmpty\' on collection, but could have been retrieved as a query.',
            57 => 'Called \'first\' on collection, but could have been retrieved as a query.',
            62 => 'Called \'contains\' on collection, but could have been retrieved as a query.',
            67 => 'Called \'count\' on collection, but could have been retrieved as a query.',
            72 => 'Called \'where\' on collection, but could have been retrieved as a query.',
            77 => 'Called \'diff\' on collection, but could have been retrieved as a query.',
            85 => 'Called \'modelKeys\' on collection, but could have been retrieved as a query.',
            90 => 'Called \'containsStrict\' on collection, but could have been retrieved as a query.',
        ]);
    }

    public function testNoFalseNegativesQuery(): void
    {
        $this->assertSeeErrorsInOrder(__DIR__ . '/Data/UnnecessaryCollectionCallsQuery.php', [
            'Called \'max\' on collection, but could have been retrieved as a query.',
            'Called \'average\' on collection, but could have been retrieved as a query.',
            'Called \'isNotEmpty\' on collection, but could have been retrieved as a query.',
            'Called \'pluck\' on collection, but could have been retrieved as a query.',
        ]);
    }
}

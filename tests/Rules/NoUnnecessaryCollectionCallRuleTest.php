<?php

declare(strict_types=1);

namespace Tests\Rules;

use NunoMaduro\Larastan\Properties\ModelPropertyExtension;
use NunoMaduro\Larastan\Rules\NoUnnecessaryCollectionCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoUnnecessaryCollectionCallRule> */
class NoUnnecessaryCollectionCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUnnecessaryCollectionCallRule($this->createReflectionProvider(), self::getContainer()->getByType(ModelPropertyExtension::class), [], []);
    }

    public function testNoFalsePositives(): void
    {
        $this->analyse([__DIR__.'/Data/CorrectCollectionCalls.php'], []);
    }

    public function testNoFalseNegativesEloquent(): void
    {
        $this->analyse([__DIR__.'/Data/UnnecessaryCollectionCallsEloquent.php'], [
            ['Called \'pluck\' on Laravel collection, but could have been retrieved as a query.', 18],
            ['Called \'count\' on Laravel collection, but could have been retrieved as a query.', 23],
            ['Called \'pluck\' on Laravel collection, but could have been retrieved as a query.', 29],
            ['Called \'count\' on Laravel collection, but could have been retrieved as a query.', 34],
            ['Called \'first\' on Laravel collection, but could have been retrieved as a query.', 39],
            ['Called \'take\' on Laravel collection, but could have been retrieved as a query.', 45],
            ['Called \'count\' on Laravel collection, but could have been retrieved as a query.', 50],
            ['Called \'isEmpty\' on Laravel collection, but could have been retrieved as a query.', 57],
            ['Called \'first\' on Laravel collection, but could have been retrieved as a query.', 62],
            ['Called \'contains\' on Laravel collection, but could have been retrieved as a query.', 67],
            ['Called \'count\' on Laravel collection, but could have been retrieved as a query.', 72],
            ['Called \'where\' on Laravel collection, but could have been retrieved as a query.', 78],
            ['Called \'diff\' on Laravel collection, but could have been retrieved as a query.', 84],
            ['Called \'modelKeys\' on Laravel collection, but could have been retrieved as a query.', 92],
            ['Called \'containsStrict\' on Laravel collection, but could have been retrieved as a query.', 97],
            ['Called \'sum\' on Laravel collection, but could have been retrieved as a query.', 103],
        ]);
    }

    public function testNoFalseNegativesQuery(): void
    {
        $this->analyse([__DIR__.'/Data/UnnecessaryCollectionCallsQuery.php'], [
            ['Called \'max\' on Laravel collection, but could have been retrieved as a query.', 15],
            ['Called \'isNotEmpty\' on Laravel collection, but could have been retrieved as a query.', 20],
            ['Called \'pluck\' on Laravel collection, but could have been retrieved as a query.', 26],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../phpstan-tests.neon'];
    }
}

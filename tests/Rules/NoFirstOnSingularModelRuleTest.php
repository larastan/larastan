<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoFirstOnSingularModelRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoFirstOnSingularModelRule> */
class NoFirstOnSingularModelRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoFirstOnSingularModelRule();
    }

    public function testRuleDetectsFirstOnModelInstance(): void
    {
        $this->analyse([__DIR__ . '/data/NoFirstOnModelInstance.php'], [
            [
                'Calling \'first()\' on an already fetched Eloquent model instance (e.g., returned by \'find()\' or \'findOrFail()\') is redundant and may cause unexpected behavior because it triggers a new query ignoring the original model context.',
                5,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

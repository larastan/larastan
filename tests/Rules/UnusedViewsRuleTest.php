<?php

namespace Rules;

use NunoMaduro\Larastan\Collectors\UsedEmailViewCollector;
use NunoMaduro\Larastan\Collectors\UsedViewFunctionCollector;
use NunoMaduro\Larastan\Rules\UnusedViewsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UnusedViewsRule> */
class UnusedViewsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new UnusedViewsRule;
    }

    protected function getCollectors(): array
    {
        return [
            new UsedViewFunctionCollector,
            new UsedEmailViewCollector,
        ];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Data/FooController.php'], [
            [
                'This view is not used in the project.',
                00,
            ],
            [
                'This view is not used in the project.',
                00,
            ],
        ]);
    }
}

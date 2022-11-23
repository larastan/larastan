<?php

namespace Rules;

use NunoMaduro\Larastan\Collectors\UsedEmailViewCollector;
use NunoMaduro\Larastan\Collectors\UsedRouteFacadeViewCollector;
use NunoMaduro\Larastan\Collectors\UsedViewFacadeMakeCollector;
use NunoMaduro\Larastan\Collectors\UsedViewFunctionCollector;
use NunoMaduro\Larastan\Collectors\UsedViewInAnotherViewCollector;
use NunoMaduro\Larastan\Collectors\UsedViewMakeCollector;
use NunoMaduro\Larastan\Rules\UnusedViewsRule;
use NunoMaduro\Larastan\Support\ViewFileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UnusedViewsRule> */
class UnusedViewsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $viewFileHelper = new ViewFileHelper([__DIR__.'/../Application/resources/views'], $this->getFileHelper());

        return new UnusedViewsRule(new UsedViewInAnotherViewCollector(
            $this->getContainer()->getService('currentPhpVersionSimpleDirectParser'),
            $viewFileHelper,
        ), $viewFileHelper);
    }

    protected function getCollectors(): array
    {
        return [
            new UsedViewFunctionCollector,
            new UsedEmailViewCollector,
            new UsedViewMakeCollector,
            new UsedViewFacadeMakeCollector,
            new UsedRouteFacadeViewCollector,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // This is a workaround for a weird PHPStan container cache issue.
        require __DIR__.'/../../bootstrap.php';
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Data/FooController.php'], [
            [
                'This view is not used in the project.',
                00,
            ],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../../extension.neon',
        ];
    }
}

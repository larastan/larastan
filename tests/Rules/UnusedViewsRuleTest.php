<?php

namespace Rules;

use NunoMaduro\Larastan\Collectors\UsedEmailViewCollector;
use NunoMaduro\Larastan\Collectors\UsedViewFacadeMakeCollector;
use NunoMaduro\Larastan\Collectors\UsedViewFunctionCollector;
use NunoMaduro\Larastan\Collectors\UsedViewInAnotherViewCollector;
use NunoMaduro\Larastan\Collectors\UsedViewMakeCollector;
use NunoMaduro\Larastan\Rules\UnusedViewsRule;
use NunoMaduro\Larastan\Support\ViewFileHelper;
use PHPStan\File\FileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UnusedViewsRule> */
class UnusedViewsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new UnusedViewsRule(new UsedViewInAnotherViewCollector(
            $this->getContainer()->getService('currentPhpVersionSimpleDirectParser'),
            new ViewFileHelper([__DIR__.'/../Application/resources/views'], $this->getContainer()->getByType(FileHelper::class)),
        ), new ViewFileHelper([__DIR__.'/../Application/resources/views'], $this->getContainer()->getByType(FileHelper::class)));
    }

    protected function getCollectors(): array
    {
        return [
            new UsedViewFunctionCollector,
            new UsedEmailViewCollector,
            new UsedViewMakeCollector,
            new UsedViewFacadeMakeCollector,
        ];
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
            __DIR__.'/phpstan-rules.neon',
        ];
    }
}

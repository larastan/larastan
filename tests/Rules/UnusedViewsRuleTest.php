<?php

declare(strict_types=1);

namespace Rules;

use Larastan\Larastan\Collectors\UsedEmailViewCollector;
use Larastan\Larastan\Collectors\UsedRouteFacadeViewCollector;
use Larastan\Larastan\Collectors\UsedViewFacadeMakeCollector;
use Larastan\Larastan\Collectors\UsedViewFunctionCollector;
use Larastan\Larastan\Collectors\UsedViewInAnotherViewCollector;
use Larastan\Larastan\Collectors\UsedViewMakeCollector;
use Larastan\Larastan\Rules\UnusedViewsRule;
use Larastan\Larastan\Support\ViewFileHelper;
use PhpParser\Node;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UnusedViewsRule> */
class UnusedViewsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $viewFileHelper = new ViewFileHelper([__DIR__ . '/../application/resources/views'], $this->getFileHelper());

        return new UnusedViewsRule(new UsedViewInAnotherViewCollector(
            $this->getContainer()->getService('currentPhpVersionSimpleDirectParser'),
            $viewFileHelper,
        ), $viewFileHelper);
    }

    /** @return array<Collector<Node, mixed>> */
    protected function getCollectors(): array
    {
        return [
            new UsedViewFunctionCollector(),
            new UsedEmailViewCollector(),
            new UsedViewMakeCollector(),
            new UsedViewFacadeMakeCollector(),
            new UsedRouteFacadeViewCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/FooController.php'], [
            [
                'This view is not used in the project.',
                00,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }
}

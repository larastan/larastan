<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\View\Factory;
use Larastan\Larastan\Collectors\UsedEmailViewCollector;
use Larastan\Larastan\Collectors\UsedRouteFacadeViewCollector;
use Larastan\Larastan\Collectors\UsedViewFacadeMakeCollector;
use Larastan\Larastan\Collectors\UsedViewFunctionCollector;
use Larastan\Larastan\Collectors\UsedViewInAnotherViewCollector;
use Larastan\Larastan\Collectors\UsedViewMakeCollector;
use Larastan\Larastan\Support\ViewFileHelper;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function array_diff;
use function array_filter;
use function collect;
use function iterator_to_array;
use function view;

/** @implements Rule<CollectedDataNode> */
final class UnusedViewsRule implements Rule
{
    /** @var list<string>|null */
    private array|null $viewsUsedInOtherViews = null;

    public function __construct(private UsedViewInAnotherViewCollector $usedViewInAnotherViewCollector, private ViewFileHelper $viewFileHelper)
    {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return RuleError[] */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->viewsUsedInOtherViews === null) {
            $this->viewsUsedInOtherViews = $this->usedViewInAnotherViewCollector->getUsedViews();
        }

        $usedViews = collect([
            $node->get(UsedViewFunctionCollector::class),
            $node->get(UsedEmailViewCollector::class),
            $node->get(UsedViewMakeCollector::class),
            $node->get(UsedViewFacadeMakeCollector::class),
            $node->get(UsedRouteFacadeViewCollector::class),
            $this->viewsUsedInOtherViews,
        ])->flatten()->unique()->toArray();

        $allViews = iterator_to_array($this->viewFileHelper->getAllViewNames());

        $existingViews = [];

        /** @var Factory $view */
        $view = view();

        foreach ($usedViews as $viewName) {
            if (! $view->exists($viewName)) {
                continue;
            }

            $existingViews[] = $viewName;
        }

        $unusedViews = array_diff($allViews, array_filter($existingViews));

        $errors = [];
        foreach ($unusedViews as $file) {
            $path = $view->getFinder()->find($file);

            $errors[] = RuleErrorBuilder::message('This view is not used in the project.')
                ->file($path)
                ->line(0)
                ->identifier('larastan.unusedViews')
                ->build();
        }

        return $errors;
    }
}

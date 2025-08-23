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
use function array_unique;
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

        /** @var Factory $factory */
        $factory = view();
        $finder  = $factory->getFinder();

        $allViews = iterator_to_array($this->viewFileHelper->getAllViewNames());

        $usedViews = static::filterExistingViews($factory, $usedViews);
        $allViews  = static::filterExistingViews($factory, $allViews);

        $unusedViews = array_unique(array_diff($allViews, $usedViews));

        $errors = [];
        foreach ($unusedViews as $file) {
            $path = $finder->find($file);

            $errors[] = RuleErrorBuilder::message('This view is not used in the project.')
                ->file($path)
                ->line(0)
                ->identifier('larastan.unusedViews')
                ->build();
        }

        return $errors;
    }

    /**
     * @param string[] $views
     *
     * @return string[]
     */
    protected static function filterExistingViews(Factory $factory, array $views): array
    {
        return array_filter($views, static function (string $view) use ($factory): bool {
            return $factory->exists($view);
        });
    }
}

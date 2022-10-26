<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use function collect;
use Illuminate\Support\Facades\File;
use Illuminate\View\Factory;
use NunoMaduro\Larastan\Collectors\UsedEmailViewCollector;
use NunoMaduro\Larastan\Collectors\UsedViewFunctionCollector;
use NunoMaduro\Larastan\Collectors\UsedViewInAnotherViewCollector;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Finder\SplFileInfo;

/** @implements Rule<CollectedDataNode> */
final class UnusedViewsRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $usedViews = collect([
            $node->get(UsedViewFunctionCollector::class),
            $node->get(UsedEmailViewCollector::class),
            $node->get(UsedViewInAnotherViewCollector::class),
        ])->flatten()->unique()->toArray();
        $allViews = array_map(function (SplFileInfo $file) {
            return $file->getPathname();
        }, array_filter(File::allFiles(resource_path('views')), function (SplFileInfo $file) {
            return ! str_contains($file->getPathname(), 'views/vendor') && str_ends_with($file->getFilename(), '.blade.php');
        }));

        $existingViews = [];

        /** @var Factory $view */
        $view = view();

        foreach ($usedViews as $viewName) {
            // Not existing views are reported with `view-string` type
            if ($view->exists($viewName)) {
                $existingViews[] = $view->getFinder()->find($viewName);
            }
        }

        $unusedViews = array_diff($allViews, $existingViews);

        $errors = [];
        foreach ($unusedViews as $file) {
            $errors[] = RuleErrorBuilder::message('This view is not used in the project.')
                ->file($file)
                ->line(0)
                ->build();
        }

        return $errors;
    }
}

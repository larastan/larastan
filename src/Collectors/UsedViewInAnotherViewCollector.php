<?php

namespace NunoMaduro\Larastan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\FileNode;

/** @implements Collector<FileNode, string[]> */
class UsedViewInAnotherViewCollector implements Collector
{
    /** @see https://regex101.com/r/8gosof/1 */
    private const VIEW_NAME_REGEX = '/^@(extends|include(If|Unless|When|First)?)(\(.*?\'(.*?)\'(\)|,))/m';

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $nodes = array_filter($node->getNodes(), function (Node $node) {
            return $node instanceof Node\Stmt\InlineHTML;
        });

        if (count($nodes) === 0) {
            return null;
        }

        $usedViews = [];

        foreach ($nodes as $node) {
            preg_match_all(self::VIEW_NAME_REGEX, $node->value, $matches, PREG_SET_ORDER, 0);

            $usedViews = array_merge($usedViews, array_map(function ($match) {
                return $match[4];
            }, $matches));
        }

        return $usedViews;
    }
}

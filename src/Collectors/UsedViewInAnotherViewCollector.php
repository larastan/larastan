<?php

namespace NunoMaduro\Larastan\Collectors;

use Generator;
use NunoMaduro\Larastan\Support\ViewFileHelper;
use PhpParser\Node;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final class UsedViewInAnotherViewCollector
{
    /** @see https://regex101.com/r/8gosof/1 */
    private const VIEW_NAME_REGEX = '/@(extends|include(If|Unless|When|First)?)(\(.*?\'(.*?)\'(\)|,))/m';

    public function __construct(private Parser $parser, private ViewFileHelper $viewFileHelper)
    {
    }

    /** @return list<string> */
    public function getUsedViews(): array
    {
        $usedViews = [];
        foreach ($this->viewFileHelper->getAllViewFilePaths() as $viewFile) {
            try {
                $parserNodes = $this->parser->parseFile($viewFile);

                $usedViews = array_merge($usedViews, $this->processNodes($parserNodes));
            } catch (ParserErrorsException $e) {
                continue;
            }
        }

        return $usedViews;
    }

    /**
     * @param Node\Stmt[] $nodes
     * @return list<string>
     */
    private function processNodes(array $nodes): array
    {
        $nodes = array_filter($nodes, function (Node $node) {
            return $node instanceof Node\Stmt\InlineHTML;
        });

        if (count($nodes) === 0) {
            return [];
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

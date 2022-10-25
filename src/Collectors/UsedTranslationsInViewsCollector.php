<?php

namespace NunoMaduro\Larastan\Collectors;

use NunoMaduro\Larastan\Support\ViewFileHelper;
use PhpParser\Node;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

final class UsedTranslationsInViewsCollector
{
    /** @see https://regex101.com/r/WEJqdL/21 */
    private const TRANSLATION_REGEX = '/[^\w](trans|trans_choice|Lang::get|Lang::choice|Lang::trans|Lang::transChoice|@lang|@choice|__|$trans.get)\((?P<quote>[\'"])(?P<string>(?:\\k{quote}|(?!\k{quote}).)*)\k{quote}[\),]/m';

    public function __construct(private Parser $parser, private ViewFileHelper $viewFileHelper)
    {
    }

    /** @return list<string> */
    public function getUsedTranslations(): array
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
     * @param  Node\Stmt[]  $nodes
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
            preg_match_all(self::TRANSLATION_REGEX, $node->value, $matches, PREG_SET_ORDER, 0);

            $usedViews = array_merge($usedViews, array_map(function ($match) {
                return $match[3];
            }, $matches));
        }

        return $usedViews;
    }
}

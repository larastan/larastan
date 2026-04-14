<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Larastan\Larastan\Support\ViewFileHelper;
use Larastan\Larastan\Support\ViewParser;
use PhpParser\Node;

use function array_filter;
use function array_map;
use function array_merge;
use function count;
use function preg_match_all;
use function str_replace;
use function substr;
use function substr_count;

use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

final class UsedTranslationViewCollector
{
    /** @see https://regex101.com/r/akPfPj/1 */
    private const TRANSLATION_REGEX = '/(?<!\w)(trans|trans_choice|Lang::get|Lang::choice|Lang::trans|Lang::transChoice|@lang|@choice|__|\$t)\((?P<quote>[\'"])(?P<string>(?:\\k{quote}|(?!\k{quote}).)*?)\k{quote}[\),]/m';

    public function __construct(private ViewParser $viewParser, private ViewFileHelper $viewFileHelper)
    {
    }

    /** @return array<string, array{0: string, 1: int}[]> */
    public function getUsedTranslations(): array
    {
        $translations = [];

        foreach ($this->viewFileHelper->getRootViewFilePaths() as $viewFile) {
            $parserNodes = $this->viewParser->getNodes($viewFile);

            $translations[$viewFile] = $this->processNodes($parserNodes);
        }

        return $translations;
    }

    /**
     * @param Node\Stmt[] $nodes
     *
     * @return array{0: string, 1: int}[]
     */
    private function processNodes(array $nodes): array
    {
        $nodes = array_filter($nodes, static function (Node $node): bool {
            return $node instanceof Node\Stmt\InlineHTML;
        });

        if (count($nodes) === 0) {
            return [];
        }

        $translations = [];

        foreach ($nodes as $node) {
            preg_match_all(self::TRANSLATION_REGEX, $node->value, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, 0);

            $translations = array_merge($translations, array_map(function (array $match) use ($node): array {
                return [
                    $this->unescapeMatch($match),
                    $this->matchLine($match, $node),
                ];
            }, $matches));
        }

        return $translations;
    }

    /** @param array{quote: array{string, int}, string: array{string, int}} $match */
    private function unescapeMatch(array $match): string
    {
        $quote = $match['quote'][0];

        return str_replace('\\' . $quote, $quote, $match['string'][0]);
    }

    /** @param array{0: array{string, int}} $match */
    private function matchLine(array $match, Node\Stmt\InlineHTML $node): int
    {
        $stringUntilMatch = substr($node->value, 0, $match[0][1]);

        return $node->getStartLine() + substr_count($stringUntilMatch, "\n");
    }
}

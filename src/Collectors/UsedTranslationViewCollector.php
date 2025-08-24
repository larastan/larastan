<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Larastan\Larastan\Support\ViewFileHelper;
use PhpParser\Node;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

use function array_filter;
use function array_map;
use function array_merge;
use function count;
use function preg_match_all;

use const PREG_SET_ORDER;

final class UsedTranslationViewCollector
{
    /** @see https://regex101.com/r/ksUgGz/1 */
    private const TRANSLATION_REGEX = '/[^\w](trans|trans_choice|Lang::get|Lang::choice|Lang::trans|Lang::transChoice|@lang|@choice|__|\$t)\((?P<quote>[\'"])(?P<string>(?:\\k{quote}|(?!\k{quote}).)*)\k{quote}[\),]/m';

    public function __construct(private Parser $parser, private ViewFileHelper $viewFileHelper)
    {
    }

    /** @return string[] */
    public function getUsedTranslations(): array
    {
        $translations = [];

        foreach ($this->viewFileHelper->getRootViewFilePaths() as $viewFile) {
            try {
                $parserNodes = $this->parser->parseFile($viewFile);

                $translations[$viewFile] = $this->processNodes($parserNodes);
            } catch (ParserErrorsException) {
                continue;
            }
        }

        return $translations;
    }

    /**
     * @param Node\Stmt[] $nodes
     *
     * @return string[]
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
            preg_match_all(self::TRANSLATION_REGEX, $node->value, $matches, PREG_SET_ORDER, 0);

            $translations = array_merge($translations, array_map(static function (array $match): array {
                return [$match[3], 0];
            }, $matches));
        }

        return $translations;
    }
}

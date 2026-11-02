<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

use function count;
use function in_array;

/** @implements Collector<Node\Expr\FuncCall, string> */
final class UsedTranslationFunctionCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @return array{0: string, 1: int}
     */
    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $node->name instanceof Node\Name) {
            return null;
        }

        if (! in_array($scope->resolveName($node->name), ['__', 'trans', 'trans_choice'], true)) {
            return null;
        }

        if (count($node->getArgs()) === 0) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return [$template->value, $node->getStartLine()];
    }
}

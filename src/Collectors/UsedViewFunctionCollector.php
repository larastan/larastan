<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Collectors;

use Illuminate\View\ViewName;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/** @implements Collector<Node\Expr\FuncCall, string> */
final class UsedViewFunctionCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    /** @param Node\Expr\FuncCall $node */
    public function processNode(Node $node, Scope $scope): ?string
    {
        $funcName = $node->name;

        if (! $funcName instanceof Node\Name) {
            return null;
        }

        $funcName = $scope->resolveName($funcName);

        if ($funcName !== 'view') {
            return null;
        }

        // TODO: maybe make sure this function is coming from Laravel

        if (count($node->getArgs()) < 1) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return ViewName::normalize($template->value);
    }
}

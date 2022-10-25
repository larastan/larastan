<?php

namespace NunoMaduro\Larastan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/** @implements Collector<Node\Expr\FuncCall, string> */
class UsedTranslationFunctionsCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope)
    {
        if (! $node->name instanceof Node\Name) {
            return null;
        }

        if (! in_array(strtolower($scope->resolveName($node->name)), ['__', 'trans', 'trans_choice'], true)) {
            return null;
        }

        if ($node->getArgs() === []) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return $template->value;
    }
}

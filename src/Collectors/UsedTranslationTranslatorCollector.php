<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Translation\Translator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;

use function count;
use function in_array;

/** @implements Collector<Node\Expr\MethodCall, string> */
final class UsedTranslationTranslatorCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    /**
     * @param Node\Expr\MethodCall $node
     *
     * @return array{0: string, 1: int}
     */
    public function processNode(Node $node, Scope $scope): array|null
    {
        $name = $node->name;

        if (! $name instanceof Node\Identifier) {
            return null;
        }

        if (! in_array($name->name, ['get', 'choice'], true)) {
            return null;
        }

        if (count($node->getArgs()) === 0) {
            return null;
        }

        $class = $node->var;

        if (! (new ObjectType(Translator::class))->isSuperTypeOf($scope->getType($class))->yes()) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return [$template->value, $node->getStartLine()];
    }
}
